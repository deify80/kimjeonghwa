<?php if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Stats_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();
		
		$this->param = $this->input->post(NULL, true);
	}
	public function set_config() {
		$pack = $this->param['pack'];
		$field = $this->param['field'];
		$value = $this->param['value'];

		$this->load->library('common_lib');
		$rs = $this->common_lib->set_config($pack, $field, $value);
		if($rs) {
			return_json(true, '');
		}
		else {
			return_json(false, '');
		}
	}

	public function get_config() {
		$pack = $this->param['pack'];
		$field = $this->param['field'];
		$this->load->library('common_lib');
		$rs = $this->common_lib->get_config($pack,$field);
		if($rs) {
			return_json(true, '');
		}
		else {
			return_json(false, '');
		}
	}
}