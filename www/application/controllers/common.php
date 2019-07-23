<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Common extends CI_Controller {

	public function __construct() {
		parent::__construct();
		session_start();
		$this->yield = TRUE;

	}

	public function post() {
		$p = $this->input->post(NULL, true);
		$datum = array(
			'ref'=>$_SERVER['SERVER_NAME'],
			'target'=> array(
				'zipcode'=>$p['zipcode'],
				'address'=>$p['address']
			)
		);
		$this->load->view( 'common/post', $datum );
	}


	public function treat() {
		$p = $this->input->post(NULL, true);

		$d3 = $this->common_lib->get_treat_row($p['no']);
		$cfg['d3'] = $this->common_lib->get_treat_children($d3['parent_no']);
		$d2 = $this->common_lib->get_treat_row($d3['parent_no']);
		$cfg['d2'] = $this->common_lib->get_treat_children($d2['parent_no']);
		$d1 = $this->common_lib->get_treat_row($d2['parent_no']);
		$cfg['d1'] = $this->common_lib->get_treat_children();

		$datum = array(
			'cfg'=>$cfg,
			'selected'=>array('d1'=>$d1['no'], 'd2'=>$d2['no'], 'd3'=>$p['no']),
			'callback'=>$p['callback'],
			'mode'=>$p['mode']
		);
		$this->_display('/common/treat', $datum, 'inc');
	}

	public function refresh() {
		$_SESSION['search'] = null;
		$this->session->unset_userdata('search');
	}

	public function set_session() {
		$p = $this->input->post(NULL, true);
		$_SESSION['search'] = array_filter($p);
	}

	private function _display($tmpl, $datum, $layout='default') {
		$tpl = "{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
