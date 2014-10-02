<?php
include 'GPG.class.php';
include 'GetConf.class.php';
include 'checkframework.php';


if (isset($argv[1])) {
	include 'moddetails.php';
} else {
	include 'checkall.php';
}
