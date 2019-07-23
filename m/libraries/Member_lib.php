<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Member_lib {

	protected $ci;

	public function __construct() {
		$this->ci =& get_instance();
	}

	public function get_user_info($user_id, $field='*') {
		$ci = $this->ci;
		$ci->load->model('user_m');
		$rs = $ci->user_m->get_info(array('user_id'=>$user_id), $field);
		return $rs;
	}

	public function insert_log($contents, $heart=0) {
		$this->ci->load->model('member_m');
		$user_id = $ci->user_m->get_login_infos('user_id');
		if($heart) {

		}
		else {
			$point_no = 0;
		}
		$record = array(
			'user_id'=>$user_id,
			'contents'=>$contents,
			'date_insert'=>NOW
		);

		$ci->member_m->insert($record, 'ssum_member_log');
	}
}
