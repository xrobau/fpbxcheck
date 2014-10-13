<?php
include 'GPG.class.php';
include 'GetConf.class.php';
include 'checkframework.php';

$shortopts  = "m:cr";

$longopts  = array(
	"module:",
	"redownload",
	"clean"
);
$options = getopt($shortopts, $longopts);

$redownload = isset($options['redownload']) ? true : false;
$clean = isset($options['clean']) ? true : false;

if (isset($options['module']) || isset($options['m'])) {
	include 'moddetails.php';
} else {
	include 'checkall.php';
}
