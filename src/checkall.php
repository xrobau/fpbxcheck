<?php

global $nagios; // Set in src/FreePBX/FreePBXCheckerCommand.php

$output->writeln('Starting integrity check...');

if($clean) {
	$output->writeln('<info>Clean defined, Will attempt to clean anything thing bad up</info>');
}

if($redownload) {
	$output->writeln('<info>Redownload defined, will attempt to redownload where needed</info>');
}

$c = new GetConf();

$gpg = new GPG($c);
$gpg->trustFreePBX();

$framework = new CheckFramework($clean, $output);

// Steal GetConf's DB connection
$db = $c->db;

// Grab all our modules.

$allmods = $db->query('select * from `modules`')->fetchAll();

$goodmods = 0;
$badmods = 0;
$othermods = 0;
$exploited = false;
$admin = false;
$quarantine = sys_get_temp_dir()."/freepbx_quarantine";

if (!file_exists($quarantine)) {
	mkdir($quarantine);
}

$framework->checkSig();

if(file_exists($c->get('AMPWEBROOT')."/admin/bootstrap.inc.php")) {
	$exploited = true;
	$output->writeln("<error>*** Exploit 'mgknight' Detected ***</error>");
	if ($nagios) {
		// Critical error.
		// For nagios, use 'print', for everything else, use $output.
		print "Attack Detected! Machine is compromised! Known Exploit CVE-2014-7235 'mgknight'\n";
		exit(2);
	}
	if (!$clean) {
		$output->writeln("<error>To fix this automatically, re-run this script with --clean</error>");
	}
}

// Check for mgknight user
$admins = $db->query('SELECT * FROM `ampusers` WHERE `username` = "mgknight"')->fetchAll();
if(count($admins) != 0) {
	$output->writeln("<error>mgknight user detected!</error>");
	if ($nagios) {
		// Warning
		print "Known bad user 'mgknight' detacted\n";
		exit(1);
	}
	if ($clean) {
		$output->writeln("\t<info>Deleting 'mgknight' user.</info>");
		$sql = "DELETE FROM ampusers WHERE username = 'mgknight'";
		$db->query($sql);
	}
}

if($clean) {
	$output->writeln("Cleaning up exploit 'mgknight'");
	if (file_exists($c->get('AMPWEBROOT')."/admin/bootstrap.inc.php")) {
		$redownload = true;
		$output->writeln("\tRemoving invalid bootstrap file");
		unlink($c->get('AMPWEBROOT')."/admin/bootstrap.inc.php");
	}

	$admins = $db->query('SELECT * FROM `ampusers` WHERE `sections` = "*"')->fetchAll();
	if(count($admins) < 1) {
		$output->writeln("\tNo Admin Users detected. Adding one now.");
		$pass = substr(hash('sha256', openssl_random_pseudo_bytes(32)), 0, 16);
		$sha1 = sha1($pass);
		$sql = "INSERT INTO ampusers (`username`, `password_sha1`, `sections`) VALUES ('admin','".$sha1."','*')";
		$db->query($sql);
		$admin['pass'] = $pass;
	}

	$output->writeln("\tPurging PHP Session storage");
	foreach(glob(session_save_path()."/sess_*") as $session) {
		if(!unlink($session)) {
			$output->writeln("<error>\t*** UNABLE TO PURGE SESSION $session ***</error>");
		}
	}
	$output->writeln("\tDone");

	$files = array("manager_custom.conf", "sip_custom.conf","extensions_custom.conf");
	foreach($files as $file) {
		if(file_exists($c->get('ASTETCDIR')."/".$file)) {
			$output->writeln("\tMoving potentially compromised file ".$c->get('ASTETCDIR')."/".$file." to ".$quarantine."/".$file);
			copy($c->get('ASTETCDIR')."/".$file,$quarantine."/".$file);
			unlink($c->get('ASTETCDIR')."/".$file);
			touch($c->get('ASTETCDIR')."/".$file);
		}
	}

	$files = array("admin/images/index.php", "fop2/css/fluid/index.php");
	foreach ($files as $file) {
		if (file_exists($c->get('AMPWEBROOT') ."/".$file)) {
			//rename the file
			$qFile = str_replace("/","_", $file);
			copy($c->get('AMPWEBROOT')."/".$file,$quarantine."/".$qFile);
			unlink($c->get('AMPWEBROOT')."/".$file);
			$output->writeln("\tMoving potentially compromised file ".$c->get('AMPWEBROOT')."/".$file." to ".$quarantine."/".$qFile);
		}
	}

	$output->writeln("<info>Cleaned potential 'mgknight' exploit. Please check your system for any suspicious activity. This script might not have removed it all!</info>");
}

$output->writeln("Checking FreePBX ARI Framework");
$fw_ari_path = $c->get('AMPWEBROOT')."/recordings/includes";
if(file_exists($fw_ari_path)) {
	exec("grep -R 'unserialize' ".$fw_ari_path, $o, $r);
	if(empty($r)) {
		$output->writeln("<error>\t*** FREEPBX ARI IS VULNERABLE ON THIS SYSTEM ***</error>");
		if ($nagios) {
			print "ARI Vulnerable to CVE-2014-7235!\n";
			exit(2);
		}
		if($clean) {
			$output->writeln("<comment>\tARI IS VULNERABLE, MOVIING TO ".$c->get('AMPWEBROOT')."/recordings ".$quarantine."/fw_ari</comment>");
			system("cp -R ".$c->get('AMPWEBROOT')."/recordings ".$quarantine."/fw_ari");
			system("rm -Rf ".$c->get('AMPWEBROOT')."/recordings/*");
		}
	}
}
$fw_ari = $db->query("SELECT * FROM modules WHERE modulename = 'fw_ari' and enabled = 1")->fetchAll();
if(!empty($fw_ari)) {
	$output->writeln("\tFreePBX ARI Framework detected as installed, attempting to update");
	system($c->get('AMPBIN')."/module_admin -f --no-warnings update fw_ari");
} else {
	//ari is disabled but check and remove the directory as well
	if(file_exists($c->get('AMPWEBROOT')."/recordings/index.php")) {
		$contents = file_get_contents($c->get('AMPWEBROOT')."/recordings/index.php");
		if(!preg_match("/Location:(.*)ucp/i",$contents)) {
			$output->writeln("<info>\tFreePBX ARI Framework is uninstalled but the folder exists, removing it</info>");
			system("rm -Rf ".$c->get('AMPWEBROOT')."/recordings");
		} else {
			$output->writeln("<info>FreePBX ARI Framework is completely removed</info>");
		}
	} else {
		$output->writeln("<info>FreePBX ARI Framework is completely removed</info>");
	}
}
$output->writeln("Finished with FreePBX ARI Framework");

$status = $framework->checkFrameworkFiles();

if(!$status && $clean) {
	$output->writeln("<error>Framework file(s) have been modified, re-downloading<error>");
	$framework->redownloadFramework();
	$output->writeln("Finished upgrading Framework! Please re-run the check.");
	exit(-1);
} elseif(!$status && !$clean) {
	$output->writeln("<fire>Framework has been unexpectedly modified.</fire>");;
	$output->writeln("<info>Please re-run with the --clean command to automatically repair</info>");
	exit(-1);

}
$output->writeln("Checked all FreePBX Framework Files");

$output->writeln("Now checking all modules");

foreach ($allmods as $modarr) {
	$mod = $modarr['modulename'];
	if ($mod == 'admindashboard') {
		$output->writeln("<error>\t*** YOU MAY HAVE BEEN HACKED ***</error>");
		$output->writeln("<error>\tThe known-bad module 'admindashboard' is present on this machine</error>");
		$exploited = true;
		if(!$clean) {
			$output->writeln("<fire>Please run with the --clean command</fire>");
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
		$output->writeln("<info>UNSIGNED MODULE $mod -- attempting to redownload</info>");
		system($c->get('AMPBIN')."/module_admin -f --no-warnings update ".$mod);
	}
	if (!file_exists($sig)) {
		$output->writeln("<comment>UNSIGNED MODULE $mod: This module isn't signed. It may be altered, and should be re-downloaded immediately.</comment>");
		$output->writeln("<info>You may add the paramater --redownload to automatically download all unsigned modules</info>");

		$othermods++;
		if ($mod == "framework") {
			$output->writeln("<error>Criticial module unsigned, can't proceed. Sorry. Please upgrade manually</error>");
			exit(-1);
		}
		continue;
	}

	// Now, we're checking a module. Skip the two annoying ones.
	if ($mod == "framework" || $mod == "fw_ari") {
		continue;
	}
	if (!$gpg->verifyFile($sig)) {
		$output->writeln("<error>*** YOU MAY HAVE BEEN HACKED ***");
		$output->writeln("<error>The signature file $sig has been altered, or, is unable to validate!</error>");
		$output->writeln("<fire>Re-download that module. Aborting!</fire>");
		exit(-1);
	}
	$sig = $gpg->verifyModule($mod);
	if ($sig['status'] == 129) {
		$goodmods++;
	} else {
		$output->writeln("<error>WARNING: Module $mod has issues. Run script again with that module name as the param</error>");
		$badmods++;
	}

}

$output->writeln("Complete. Summary:\n\t<info>Good modules: $goodmods</info>\n\t<error>Bad modules: $badmods</error>\n\t<comment>Signature Missing: $othermods</comment>");
if($exploited) {
	$output->writeln("<fire>**** SYSTEM WAS EXPLOITED ****</fire>");
}
$output->writeln("Re-run this script with -m <rawmodname> for further information\nExample: -m ucp");
if($admin !== false) {
	$output->writeln("<info>Admin user created with random password for security with password '".$admin['pass']."'</info>");
}
exit;
