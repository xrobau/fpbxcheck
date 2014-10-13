<?php

function checkFramework($hashes, $c) {
	$webroot = $c->get('AMPWEBROOT');
	$agidir = $c->get('ASTAGIDIR');
	$sbindir = $c->get('AMPSBIN');
	$bindir = $c->get('AMPBIN');
	$status = true;

	foreach ($hashes as $file => $hash) {
		if (substr($file,0,9) == "upgrades/" || substr($file,0,16) == "amp_conf/astetc/" || substr($file,0,16) == "amp_conf/sounds/" ||
			substr($file,0,7) == "utests/" || $file == "module.xml" || $file == "libfreepbx.install.php" ) {
			continue;
		}
		if (substr($file,0,17) == "amp_conf/agi-bin/") {
			$s = validate("$agidir/".substr($file,17), $hash);
			if(!$s) {
				print "$agidir/".substr($file,17)." has been modified!\n";
				$status = false;
			}
			continue;
		}
		if (substr($file,0,14) == "amp_conf/sbin/") {
			$s = validate("$sbindir/".substr($file,14), $hash);
			if(!$s) {
				print "$sbindir/".substr($file,14)." has been modified!\n";
				$status = false;
			}
			continue;
		}
		if (substr($file,0,13) == "amp_conf/bin/") {
			if($file != "amp_conf/bin/amportal") {
				$s = validate("$bindir/".substr($file,13), $hash);
				if(!$s) {
					print "$bindir/".substr($file,13)." has been modified!\n";
					$status = false;
				}
			}
			continue;
		}
		if (substr($file,0,16) == "amp_conf/htdocs/") {
			$s = validate("$webroot/".substr($file,16), $hash);
			if(!$s) {
				print "$webroot/".substr($file,16)." has been modified!\n";
				$status = false;
			}
			continue;
		}

		if (strpos($file, "/") === false || substr($file,0,4) == "SQL/") {
			// Part of the root of the module
			$s = validate("$webroot/admin/modules/framework/$file", $hash);
			if(!$s) {
				print "$webroot/admin/modules/framework/$file has been modified!\n";
				$status = false;
			}
			continue;
		}
	}
	return $status;
}

function validate($file, $hash) {
	if (!file_exists($file)) {
		print "*** File ($file) is missing! ****\n";
		return false;
	}
	if (hash_file('sha256', $file) != $hash) {
		print "*** Mismatch on $file ****\n";
		return false;
	}
	return true;
}
