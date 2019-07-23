<?php if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Member_m extends CI_Model {

	var $tbl = array();
	public function __construct() {
		parent::__construct();

		//$this->tbl = $this->config->item('tables');
	}

	public function select_member_row() {
	}
}
