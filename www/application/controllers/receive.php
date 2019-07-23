<?php
/**
 * 수납관리
 * 작성 : 2015.05.20
 * @author 이혜진
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Receive extends CI_Controller {

	var $dataset;
	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;

		$this->load->model( array (
			// 'User_model',
			// 'Attendance_model'
		) );
	}

	public function received() {
		$data = array();
		$this->_display('received', $datum );
	}

	public function receivable() {
		$data = array();
		$this->_display('receivable', $datum );
	}


	private function _display($tmpl, $datum) {
		$this->load->view('/hospital/receive/'.$tmpl, $datum );
	}
}
