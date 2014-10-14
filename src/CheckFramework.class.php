<?php

class CheckFramework {
	public $webroot;
	public $agidir;
	public $sbindir;
	public $bindir;
	public $status;

	public $repar;

	private $sig;

	private $c;
	private $gpg;

	public function __construct($repair = false) {
		$c = new  GetConf(); 
		$this->webroot = $c->get('AMPWEBROOT');
		$this->agidir = $c->get('ASTAGIDIR');
		$this->sbindir = $c->get('AMPSBIN');
		$this->bindir = $c->get('AMPBIN');
		$this->sig = $c->get('AMPWEBROOT')."/admin/modules/framework/module.sig";
		$this->gpg = new GPG($c);

		$this->repair = $repair;
	}

	private function skipFile($file) {
		if ($file == "libfreepbx.install.php" || $file == "module.xml" || substr($file,0,7) == "utests/" || 
			substr($file,0,9) == "upgrades/" || substr($file,0,16) == "amp_conf/astetc/" || 
			substr($file,0,16) == "amp_conf/sounds/") {
				return true;
			}
		// else
		return false;
	}

	public function redownloadFramework() {
		$output->writeln("<warn>Downloading Framework</warn>");
		system($this->bindir."/module_admin -f --no-warnings update framework");
		$output->writeln("<info>Download complete</info>");
	}

	public function checkSig($abort = false) {
		$output->writeln('Checking Framework for a valid signature...');
		if (!file_exists($this->sig)) {
			$output->writeln("<error>Framework is unsigned!</error>");
			$this->redownloadFramework();
			$output->writeln("<info>Checking signature again.</info>");
			if (!file_exists($this->sig)) {
				$output->writeln("<error>ERROR! Framework STILL isn't signed. Can't continue.</error>");
				exit(-1);
			}
		}
		if (!$this->gpg->verifyFile($sig)) {
			if ($abort) {
				$output->writeln("<error>ERROR! Unable to successfully install framework.</error>");
				exit(-1);
			}
			$output->writeln("<error>ERROR! Framework signature file altered</error>");
			$output->writeln("<error>YOU MAY HAVE BEEN HACKED.</error>");
			if($this->repair) {
				$this->redownloadFramework();
				$output->writeln("<info>Checking signature again.</info>");
				return $this->checkSig(true);
			} else {
				$output->writeln("<info>Please run with the --clean command</info>");
				exit(-1);
			}
		} else {
			$output->writeln("<info>Framework appears to be good</info>");
			return true;
		}
	}

	public function checkFrameworkFiles() {
		$output->writeln("Now Verifying all FreePBX Framework Files");
		$out = $this->gpg->checkSig($this->sig);
		$status = checkFramework($out['hashes'],$c,$output);
		$hashes = $out['hashes'];
		foreach ($hashes as $file => $hash) {
			if ($this->skipFile($file)) {
				continue;
			}
			if (substr($file,0,17) == "amp_conf/agi-bin/") {
				$s = $this->validate("$agidir/".substr($file,17), $hash, $output);
				if(!$s) {
					$output->writeln("<error>$agidir/".substr($file,17)." has been modified!</error>");
					$status = false;
				}
				continue;
			}
			if (substr($file,0,14) == "amp_conf/sbin/") {
				$s = $this->validate("$sbindir/".substr($file,14), $hash, $output);
				if(!$s) {
					$output->writeln("<error>$sbindir/".substr($file,14)." has been modified!</error>");
					$status = false;
				}
				continue;
			}
			if (substr($file,0,13) == "amp_conf/bin/") {
				if($file != "amp_conf/bin/amportal") {
					$s = $this->validate("$bindir/".substr($file,13), $hash, $output);
					if(!$s) {
						$output->writeln("<error>$bindir/".substr($file,13)." has been modified!</error>");
						$status = false;
					}
				}
				continue;
			}
			if (substr($file,0,16) == "amp_conf/htdocs/") {
				$s = $this->validate("$webroot/".substr($file,16), $hash, $output);
				if(!$s) {
					$output->writeln("<error>$webroot/".substr($file,16)." has been modified!</error>");
					$status = false;
				}
				continue;
			}

			if (strpos($file, "/") === false || substr($file,0,4) == "SQL/") {
				// Part of the root of the module
				$s = $this->validate("$webroot/admin/modules/framework/$file", $hash, $output);
				if(!$s) {
					$output->writeln("<error>$webroot/admin/modules/framework/$file has been modified!</error>");
					$status = false;
				}
				continue;
			}
		}
		return $status;
	}

	public function validate($file, $hash,$output) {
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
}
