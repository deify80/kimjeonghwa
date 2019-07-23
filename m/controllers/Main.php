<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {

	public function index() {

		$assign = array(
			'auth'=>array('mobile_pc'=>$this->common_lib->check_auth_group('mobile_pc'))
		);
		$this->_display('main', $assign, 'sub');
	}


	private function _display($tpl, $assign, $layout="sub") {

		$this->layout_lib->default_($tpl.'.html', $assign, $layout);
		$this->layout_lib->print_();
	}
}
