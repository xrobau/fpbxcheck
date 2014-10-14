<?php

function checkFramework($hashes, $c,$output) {
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
			$s = validate("$agidir/".substr($file,17), $hash, $output);
			if(!$s) {
				$output->writeln("<error>$agidir/".substr($file,17)." has been modified!</error>");
				$status = false;
			}
			continue;
		}
		if (substr($file,0,14) == "amp_conf/sbin/") {
			$s = validate("$sbindir/".substr($file,14), $hash, $output);
			if(!$s) {
				$output->writeln("<error>$sbindir/".substr($file,14)." has been modified!</error>");
				$status = false;
			}
			continue;
		}
		if (substr($file,0,13) == "amp_conf/bin/") {
			if($file != "amp_conf/bin/amportal") {
				$s = validate("$bindir/".substr($file,13), $hash, $output);
				if(!$s) {
					$output->writeln("<error>$bindir/".substr($file,13)." has been modified!</error>");
					$status = false;
				}
			}
			continue;
		}
		if (substr($file,0,16) == "amp_conf/htdocs/") {
			$s = validate("$webroot/".substr($file,16), $hash, $output);
			if(!$s) {
				$output->writeln("<error>$webroot/".substr($file,16)." has been modified!</error>");
				$status = false;
			}
			continue;
		}

		if (strpos($file, "/") === false || substr($file,0,4) == "SQL/") {
			// Part of the root of the module
			$s = validate("$webroot/admin/modules/framework/$file", $hash, $output);
			if(!$s) {
				$output->writeln("<error>$webroot/admin/modules/framework/$file has been modified!</error>");
				$status = false;
			}
			continue;
		}
	}
	return $status;
}

function validate($file, $hash,$output) {
	if (!file_exists($file)) {
		$output->writeln("<error>*** File ($file) is missing! ****</error>");
		return false;
	}
	if (hash_file('sha256', $file) != $hash) {
		$output->writeln("<error>*** Mismatch on $file ****</error>");
		return false;
	}
	return true;
}
