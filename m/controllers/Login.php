<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model(array('Manage_m'));
	}

	public function index() {
		$hst_code = $this->Manage_m->get_hst_code();

		$assign = array(
			'hst_code'=>$hst_code
		);

		$this->_display('login', $assign, 'main');
	}

	private function _display($tpl, $assign, $layout="sub") {
		$this->layout_lib->default_($tpl.'.html', $assign, $layout);
		$this->layout_lib->print_();
	}
}
