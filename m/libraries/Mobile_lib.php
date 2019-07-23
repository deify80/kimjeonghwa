.php
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mobile_lib {

	protected $ci;

	public function __construct() {
		$this->ci =& get_instance();
	}

	function api($mode) {
		$key = md5('hbps'.date('Ymd'));
		switch ($mode) {
			case 'category':
				$url = 'http://cmltd.kr/api/category?id='.$key;
				break;
			case 'media':
				$url = 'http://dev.cmltd.kr/api/get_cfg/media';
				break;
		}
		$json = file_get_contents($url);
		return json_decode($json, true);
	}
}
