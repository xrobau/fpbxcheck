<?php

echo "Starting integrity check...\n";

$c = new GetConf();

$gpg = new GPG($c);

$gpg->trustFreePBX();

// Steal GetConf's DB connection
$db = $c->db;

// Grab all our modules.

$allmods = $db->query('select * from `modules`')->fetchAll();

$goodmods = 0;
$badmods = 0;
$othermods = 0;
$exploited = false;
$quarantine = sys_get_temp_dir()."/freepbx_quarantine";

if (!file_exists($quarantine)) {
	mkdir($quarantine);
}

system("amportal a ma upgrade framework");

print "Checking framework...";
$sig = $c->get('AMPWEBROOT')."/admin/modules/framework/module.sig";
if (!file_exists($sig)) {
	print "ERROR! Framework isn't signed. Can't continue.\n";
	exit(-1);
}
if (!$gpg->verifyFile($sig)) {
	print "ERROR! Framework signature file altered.\n\tYOU MAY HAVE BEEN HACKED.\n";
	exit(-1);
}

if (file_exists($c->get('AMPWEBROOT')."/admin/bootstrap.inc.php")) {
	$exploited = true;
	print "ERROR! Known bad file /var/www/html/admin/bootstrap.inc.php file exists!\n";
	print "It's possible that your machine has been hacked. Remove this file urgently!\n";
	if(!$clean) {
		exit(-1);
	} else {
		print "Cleaning up exploit 'mgknight'\n";
		$redownload = true;
		print "Removing invalid bootstrap file\n";
		unlink($c->get('AMPWEBROOT')."/admin/bootstrap.inc.php");
		$sql = "DELETE FROM ampusers WHERE username = 'mgknight'";
		$db->query($sql);
		print "Deleting mgknight user\n";
		$files = array("manager_custom.conf", "sip_custom.conf","extensions_custom.conf");
		foreach($files as $file) {
			if(file_exists($c->get('ASTETCDIR')."/".$file)) {
				print "Moving potentially compromised file ".$c->get('ASTETCDIR')."/".$file." to ".$quarantine."/".$file."\n";
				copy($c->get('ASTETCDIR')."/".$file,$quarantine."/".$file);
				unlink($c->get('ASTETCDIR')."/".$file);
				touch($c->get('ASTETCDIR')."/".$file);
			}
		}
		print "Exploit Cleaned. Please check your system for any suspicious activity. This script might not have removed it all!\n";
	}
}
print "OK\n";

//select * from modules where modulename = 'ucp' and enabled = 1;
$fw_ari = $db->query("SELECT * FROM modules WHERE modulename = 'fw_ari' and enabled = 1")->fetchAll();
if(!empty($fw_ari)) {
	print "FreePBX ARI Framework detected as installed, attempting to update\n";
	system("amportal a ma upgrade fw_ari");
} else {
	//ari is disabled but check and remove the directory as well
	if(file_exists($c->get('AMPWEBROOT')."/recordings/index.php")) {
		$contents = file_get_contents($c->get('AMPWEBROOT')."/recordings/index.php");
		if(!preg_match("/Location:(.*)ucp/i",$contents)) {
			print "FreePBX ARI Framework is uninstalled but the folder exists, removing it\n";
			system("rm -Rf ".$c->get('AMPWEBROOT')."/recordings");
		}
	}
}

$out = $gpg->checkSig($sig);
checkFramework($out['hashes']);

foreach ($allmods as $modarr) {
	$mod = $modarr['modulename'];
	if ($mod == 'admindashboard') {
		print "*** YOU MAY HAVE BEEN HACKED ***\n";
		print "The known-bad module 'admindashboard' is present on this machine\n";
		$exploited = true;
		if(!$clean) {
			exit(-1);
		} else {
			if(file_exists($c->get('AMPWEBROOT')."/admin/modules/admindashboard")) {
				system("rm -Rf ".$c->get('AMPWEBROOT')."/admin/modules/admindashboard");
				system("amportal a ma delete $mod");
				if(file_exists(sys_get_temp_dir()."/c2.pl")) {
					unlink(sys_get_temp_dir()."/c2.pl");
				}
                                if(file_exists(sys_get_temp_dir()."/c.sh")) {
                                        unlink(sys_get_temp_dir()."/c.sh");
                                }
			}
		}
	}
	$sig = $c->get('AMPWEBROOT')."/admin/modules/$mod/module.sig";
	if (!file_exists($sig) && $redownload) {
		print "UNSIGNED MODULE $mod -- attempting to redownload\n";
		system("amportal a ma download $mod");
		system("amportal a ma install $mod");
	}
	if (!file_exists($sig)) {
		print "UNSIGNED MODULE $mod: This module isn't signed. It may be altered, and should be re-downloaded immediately.\n";
		print "You may add the paramater --redownload to automatically download all unsigned modules\n";

		$othermods++;
		if ($mod == "framework") {
			print "Criticial module unsigned, can't proceed. Sorry. Please upgrade manually\n";
			exit(-1);
		}
		continue;
	}

	// Now, we're checking a module. Skip the two annoying ones.
	if ($mod == "framework" || $mod == "fw_ari") {
		continue;
	}
	if (!$gpg->verifyFile($sig)) {
		print "*** YOU MAY HAVE BEEN HACKED ***\n";
		print "The signature file $sig has been altered, or, is unable to validate!\n";
		print "Re-download that module. Aborting!\n";
		exit(-1);
	}
	$sig = $gpg->verifyModule($mod);
	if ($sig['status'] == 129) {
		$goodmods++;
	} else {
		print "WARNING: Module $mod has issues. Run script again with that module name as the param\n";
		$badmods++;
	}

}

print "Complete. Summary:\n\tGood modules: $goodmods\n\tBad modules: $badmods\n\tSignature Missing: $othermods\n";
if($exploited) {
	print "**** SYSTEM WAS EXPLOITED ****\n";
}
print "Re-run this script with any module name for further information\n";
exit;

