<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Templatex
{
	private $ci;
	function __construct()
	{
		if ( ! class_exists('Template_') )
		{
			require_once(BASEPATH.'libraries/Template_/Template_.php');
			// require_once(BASEPATH.'libraries/adodb5/adodb-error.inc.php');
		}

		$this->ci =& get_instance();
		$this->_init_adodb_template($ci);
	}

	private function _init_adodb_template(&$ci) {
		$this->ci->template_=new Template_;
		$this->ci->template_->compile_dir = APPPATH.'libraries/Template_/cache';
		$this->ci->template_->cache_dir = APPPATH.'libraries/Template_/compile';
	}
}
?>
