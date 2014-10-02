<?php

echo "Starting integrity check...\n";
include 'GPG.class.php';
include 'GetConf.class.php';

$c = new GetConf();

$gpg = new GPG($c);

$gpg->trustFreePBX();

// Steal GetConf's DB connection
$db = $c->db;

// Grab all our modules.

$allmods = $db->query('select * from `modules`')->fetchAll();

$goodmods = 0;
$badmods = 0;

foreach ($allmods as $modarr) {
	$mod = $modarr['modulename'];
	if ($mod == 'admindashboard') {
		print "*** YOU MAY HAVE BEEN HACKED ***\n";
		print "The known-bad module 'admindashboard' is present on this machine\n";
		exit(-1);
	}
	$sig = $c->get('AMPWEBROOT')."/admin/modules/$mod/module.sig";
	if (!file_exists($sig)) {
		print "UNSIGNED MODULE $mod: This module isn't signed. It may be altered, and should be re-downloaded immediately.\n";
		if ($mod == "framework") {
			print "Criticial module unsigned, can't proceed. Sorry. Please upgrade manually\n";
			exit(-1);
		}
		continue;
	}

	// Now, we're checking a module. Skip framework for the moment.
	if ($mod == "framework") {
		continue;
	}
	if (!$gpg->verifyFile($sig)) {
		print "*** YOU MAY HAVE BEEN HACKED ***\n";
		print "The signature file $sig has been altered, or, is unable to validate!\n";
		print "Re-download that module. Aborting!\n";
		exit(-1);
	}
	$sig = $gpg->verifyModule($mod);
	if ($sig['status'] == GPG::STATE_TRUSTED & GPG::STATE_GOOD) {
		$goodmods++;
	} else {
		print "WARNING: Module $mod has issues. Run script again with that module name as the param\n";
	}

}

