<?php
include 'GPG.class.php';
include 'GetConf.class.php';
include 'checkframework.php';

if (isset($argv[1]) && substr($argv[1],0,12) == "--redownload") {
	$redownload = true;
	array_shift($argv);
} else {
	$redownload = false;
}

if (isset($argv[1])) {
	include 'moddetails.php';
} else {
	include 'checkall.php';
}
