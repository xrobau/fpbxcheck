<?php

echo "Starting integrity check...\n";
include 'GPG.class.php';
include 'GetConf.class.php';

$c = new GetConf();

$gpg = new GPG();

// Steal GetConf's DB connection
$db = $c->db;

// Grab all our modules.

$allmods = $db->query('select * from `modules`')->fetchAll();

foreach ($allmods as $modarr) {
	$mod = $modarr['modulename'];
	if ($mod == 'admindashboard') {
		print "*** YOU MAY HAVE BEEN HACKED ***\n";
		print "The known-bad module 'admindashboard' is present on this machine\n";
		exit(-1);
	}
	$sig = $c->get('AMPWEBROOT')."/admin/modules/$mod/module.sig";
	if (!file_exists($sig)) {
		print "Warning: Module $mod isn't signed. Can't validate\n";
		if ($mod == "framework") {
			print "Criticial module unsigned, can't proceed. Sorry. Please upgrade manually\n";
			exit(-1);
		}
	}

	// Now, we're checking a module. Skip framework for the moment.
	if ($mod == "framework") {
		continue;
	}
	$sigfile = $gpg->checkSig($sig);
	print_r($sigfile);

}

