<?php

function checkFramework($hashes) {
	$webroot = "/var/www/html";
	$agidir = "/var/lib/asterisk/agi-bin";
	$sbindir = "/usr/local/sbin";
	$bindir = "/var/lib/asterisk/bin";

	foreach ($hashes as $file => $hash) {
		if (substr($file,0,9) == "upgrades/" || substr($file,0,16) == "amp_conf/astetc/" || substr($file,0,16) == "amp_conf/sounds/" ||
			substr($file,0,7) == "utests/" || $file == "module.xml" || $file == "libfreepbx.install.php" ) {
			continue;
		}
		if (substr($file,0,17) == "amp_conf/agi-bin/") {
			validate("$agidir/".substr($file,17), $hash);
			continue;
		}
		if (substr($file,0,14) == "amp_conf/sbin/") {
			validate("$sbindir/".substr($file,14), $hash);
			continue;
		}
		if (substr($file,0,13) == "amp_conf/bin/") {
			validate("$bindir/".substr($file,13), $hash);
			continue;
		}
		if (substr($file,0,16) == "amp_conf/htdocs/") {
			validate("$webroot/".substr($file,16), $hash);
			continue;
		}

		if (strpos($file, "/") === false || substr($file,0,4) == "SQL/") {
			// Part of the root of the module
			validate("$webroot/admin/modules/framework/$file", $hash);
			continue;
		}
		print "doing $file\n";
		exit;
	}
}

function validate($file, $hash) {
	if (!file_exists($file)) {
		print "File ($file) is missing!\n";
		return false;
	}
	if (hash_file('sha256', $file) != $hash) {
		print "Mismatch on $file\n";
	}
}
