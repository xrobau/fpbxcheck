#!/usr/bin/env php
<?php
$files = array("loader.php", "moddetails.php", "checkall.php", "GPG.class.php", "GetConf.class.php");

$files[] = "86CE877469D2EAD9.key";
$files[] = "9F9169F4B33B4659.key";

$outfile = "sigcheck.phar";
$srcRoot = "src";

@unlink($outfile);

$phar = new Phar($outfile);
foreach ($files as $f) {
          $phar->addFile($f);
}
$stub = "#!/usr/bin/env php\n".$phar->createDefaultStub($files[0]);
$phar->setStub($stub);

unset($phar);
chmod($outfile, 0755);

