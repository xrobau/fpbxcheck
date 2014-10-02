<?php

$mod = $argv[1];
echo "Checking module $mod...\n";
$c = new GetConf();
$gpg = new GPG($c);
$gpg->trustFreePBX();
// Steal GetConf's DB connection
$db = $c->db;
$sig = $c->get('AMPWEBROOT')."/admin/modules/$mod/module.sig";
if (!file_exists($sig)) {
	print "UNSIGNED MODULE $mod: This module isn't signed. It may be altered, and should be re-downloaded immediately.\n";
	print "You may be able to run:\n\tamportal a ma download $mod\nto resolve this\n";
	exit;
}

if (!$gpg->verifyFile($sig)) {
	print "*** YOU MAY HAVE BEEN HACKED ***\n";
	print "The signature file $sig has failed validation\n";
	print "This means that either your machine has a malfunctioning GPG implementation,\n";
	print "or someone has altered the signature file. This should never happen\n";
	print "Please re-download this module!\n";
}
$sig = $gpg->verifyModule($mod);
print "Status: ".$sig['status']."\n";
if ($sig['status']&GPG::STATE_GOOD) {
	print "Signature Good!\n";
} else {
	print "Signature ERROR!\n";
}
if ($sig['status']&GPG::STATE_TRUSTED) {
	print "GPG Trust OK\n";
} else {
	print "GPG Trust FAILURE!\n";
}

if ($sig['status']&GPG::STATE_TAMPERED) {
	print "Module has been tampered!\n";
}

if ($sig['details']) {
	print "Result of file validation check:\n";
	print join("\n", $sig['details']);
	print "\n";

}
