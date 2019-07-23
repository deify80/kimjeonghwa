<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Common_lib {

	protected $ci;

	public function __construct() {
		$this->ci =& get_instance();
	}

	function api($mode) {
		switch ($mode) {
			case 'media' :
				$rs = $this->get_code(array('group_code'=>'04','depth'=>'2', 'use_flag'=>'Y'));
			break;
		}
		return $rs;
	}

	function get_code($where, $key='code', $value='title') {
		$this->ci->load->model('Manage_m');
		$info = $this->ci->Manage_m->select_code($where);
		foreach($info as $row) {
			$code[$row[$key]] = $row[$value];
		}
		return $code;
	}


	function get_config($type, $v) {
		$this->ci->load->model('settings_m');
		$where = array(
			"cfg_{$type}"=>$v
		);

		$rs = $this->ci->settings_m->select_config($where);
		$config = array();
		foreach($rs as $row) {
			$config[$row['cfg_group']][$row['cfg_field']] = $row['cfg_value'];
		}

		return $config;
	}

	function set_where($where) {

		if(is_array($where)) {
			foreach ($where as $field=>$value) {
				if(empty($field)) continue;
				if(is_array($value)) {
					if(isset($value['type'])) {
						switch($value['type']) {
							case 'escape':
								$this->ci->db->set($field, $value['value'], FALSE);
							break;
							case 'or':
								$this->ci->db->or_where($field, $value['value']);
							break;
						}
					}
					else {
						$this->ci->db->where_in($field, $value);
					}
				}
				else {
					if(is_null($value)) {
						$this->ci->db->where($field);
					}
					else {
						$this->ci->db->where($field, $value);
					}
				}
			}
		}
	}

	function manufacture_mobile($mobile, $manager_team_code='', $auth_group='ex_phone') {
		$mobile = format_mobile($mobile);
		return $this->protect_patient($mobile, $manager_team_code, $auth_group, 'mobile');
	}

	function protect_patient($data, $manager_team_code, $auth_group='ex_phone', $field='mobile') {
		$auth = $this->check_auth_group($auth_group);
		if($auth) {
			return $data;
		}

		if($manager_team_code == $this->ci->session->userdata('ss_team_code')) {
			return $data;
		}

		switch($field) {
			case 'mobile':
				return  preg_replace('/-(\d+)-/e',"'-'.str_repeat('*',strlen('\\1')).'-'",format_mobile($data));
			break;
			case 'email':
				return preg_replace("/([^\@\.])/", "*", $data);
			break;
			case 'tel':
				return preg_replace("/([^\-])/", "*", $data);
			break;
			case 'address':
				return preg_replace("/([^\s])/", "*", $data);
			break;
			case 'jumin':
				return ($data)?substr($data, 0,6).'-*******':'';
			break;
		}
	}

	function check_auth_group($group_code) {
		$this->ci->load->Model('Manage_m');
		$row = $this->ci->Manage_m->select_group_row(array('group_code'=>$group_code));
		if($row['is_use'] != 'Y') return false;

		$auth_type = $row['type'];
		if($row['users'] == 'all') {
			$has_grant = true;
		}
		else {
			$auth_list = explode(',',$row['users']);
			$has_grant = false;

			foreach($auth_list as $auth) {
				if($has_grant) break;

				$info = explode('_',$auth);
				switch(count($info)) {
					case '1':
						if($info[0] == $this->ci->session->userdata('ss_dept_code')) $has_grant=true;
					break;
					case '2':
						if($info[1] == $this->ci->session->userdata('ss_team_code')) $has_grant=true;
					break;
					case '3':
						if($info[2] == $this->ci->session->userdata('ss_user_no')) $has_grant=true;
					break;
				}
			}
		}

		if($auth_type == 'include') {
			return $has_grant;
		}
		else {
			return !$has_grant;
		}
	}

}
