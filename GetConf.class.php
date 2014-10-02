<?php

class GetConf {
        public static $vars;

	public $db;

        public function __construct() {
		self::$vars = array();
                if (!file_exists('/etc/freepbx.conf')) {
                        throw new Exception("Only supports /etc/freepbx.conf");
                }

                $this->parseConf('/etc/freepbx.conf');
		$dsn = "mysql:host=".self::$vars['AMPDBHOST'].";dbname=".self::$vars['AMPDBNAME'];
		$this->db = new PDO($dsn, self::$vars['AMPDBUSER'], self::$vars['AMPDBPASS']);
        }

        private function parseConf($file) {
                $f = file($file);
                foreach ($f as $line) {
			if (preg_match("/amp_conf\['(.+)'\].+'(.+)'/",$line, $out)) {
				self::$vars[$out[1]] = $out[2];
                        }
                }
        }

	public function get($conf) {
		if (isset(self::$vars[$conf])) {
			return self::$vars[$conf];
		}

		$res = $this->db->query("SELECT * FROM freepbx_settings WHERE `keyword`='$conf'")->fetchAll();
		if (!isset($res[0])) {
			throw new \Exception("No setting $conf");
		}
		return $res[0]['value'];
	}
}
