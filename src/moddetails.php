<?php

$output->writeln("Checking module $mod...");
$c = new GetConf();
$gpg = new GPG($c);
$gpg->trustFreePBX();
// Steal GetConf's DB connection
$db = $c->db;
if($mod == "framework") {
	$sig = $c->get('AMPWEBROOT')."/admin/modules/framework/module.sig";
	$out = $gpg->checkSig($sig);
	$output->writeln("Now Verifying all FreePBX Framework Files");
	$status = checkFramework($out['hashes'],$c,$output);
	$sig = $gpg->verifyModule($mod);
	if($status) {
		$output->writeln("<info>Signature Good!</info>");
	} else {
		$output->writeln("<error>Signature ERROR!</error>");
	}
	if ($sig['status']&GPG::STATE_TRUSTED) {
		$output->writeln("<info>GPG Trust OK</info>");
	} else {
		$output->writeln("<error>GPG Trust FAILURE!</info>");
	}
	die();
}
if($mod == "fw_ari") {
	$output->writeln("Unsupported module");
	exit(-1);
}
$sig = $c->get('AMPWEBROOT')."/admin/modules/$mod/module.sig";
if (!file_exists($sig)) {
	$output->writeln("<comment>UNSIGNED MODULE $mod: This module isn't signed. It may be altered, and should be re-downloaded immediately.</comment>");
	$output->writeln("<info>You may be able to run:\n\tamportal a ma download $mod\nto resolve this</info>");
	exit;
}

if (!$gpg->verifyFile($sig)) {
	$output->writeln("<fire>*** YOU MAY HAVE BEEN HACKED ***</fire>");
	$output->writeln("The signature file $sig has failed validation");
	$output->writeln("This means that either your machine has a malfunctioning GPG implementation,");
	$output->writeln("or someone has altered the signature file. This should never happen");
	$output->writeln("Please re-download this module!");
}
$sig = $gpg->verifyModule($mod);
$output->writeln("Status: ".$sig['status']);
if ($sig['status']&GPG::STATE_GOOD) {
	$output->writeln("<info>Signature Good!</info>");
} else {
	$output->writeln("<error>Signature ERROR!</error>");
}
if ($sig['status']&GPG::STATE_TRUSTED) {
	$output->writeln("<info>GPG Trust OK</info>");
} else {
	$output->writeln("<error>GPG Trust FAILURE!</info>");
}

if ($sig['status']&GPG::STATE_TAMPERED) {
	$output->writeln("<fire>Module has been tampered!</fire>");
}

if ($sig['details']) {
	print "Result of file validation check:\n";
	print join("\n", $sig['details']);
	print "\n";

}
