<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Common_lib {
	private $ci;
	function __construct() {
		$this->ci =& get_instance();
	}

	/**
	 * 부서 소속 팀 데이터
	 * @param  [type] $dept_code [description]
	 * @return [type]            [description]
	 */
	function get_team($dept_code, $status=1, $biz_id='') {
		$this->ci->load->Model('User_model');
		$list = $this->ci->User_model->get_team_list( $dept_code, $status, $biz_id);
		return $list;
	}

	// 20170202 kruddo dept_code가 아닌 team_code로 검색
	function get_team_code($dept_code, $status=1, $biz_id='') {
		$this->ci->load->Model('User_model');
		$list = $this->ci->User_model->get_team_code_list( $dept_code, $status, $biz_id);
		return $list;
	}
	// 20170202 kruddo dept_code가 아닌 team_code로 검색


	function get_user($team_code='', $key_field='user_id') {
		$this->ci->load->Model('User_model');
		$list = $this->ci->User_model->get_team_user( $team_code, '', $key_field);
		return $list;
	}

	function search_user($where, $field='*') {
		$this->ci->load->Model('User_model');
		$list = $this->ci->User_model->get_user_all($where, $field);
		return $list;
	}

	function get_user_row($where, $field='*') {
		$this->ci->load->Model('User_model');
		$info = $this->ci->User_model->get_info($where, $field);
		return $info;
	}

	function get_user_info($user_id, $field=array('name','team')) {
		$user = $this->get_user_row(array('user_id'=>$user_id), 'name, dept_code, team_code, position_code');

		$info = array();
		foreach($field as $f) {
			switch($f) {
				case 'name':
					$v = $user['name'];
				break;
				case 'team':
					$cfg_team = $this->get_team();
					$v = $cfg_team[$user['team_code']];
				break;
				case 'dept':
				break;
			}

			$info[$f] = $v;
		}

		return $info;
	}

	function get_biz_group($represent=false) {
		$CI = $this->ci;
		switch ($CI->session->userdata('ss_biz_id')) {
			case 'ezham':
			case 'ezham_cn':
				if($represent) {
					$group = 'ezham';
				}
				else {
					$group = array('ezham','ezham_cn');
				}

			break;
			default:
				$group = $CI->session->userdata('ss_biz_id');
			break;
		}

		return $group;

	}

	/**
	 * [set_where description]
	 * @param [type] $where [description]
	 */
	function set_where($where, $escape='TRUE') {

		if(is_array($where)) {
			foreach ($where as $field=>$value) {
				if(empty($field)) continue;
				if(is_array($value)) {

					if(isset($value['type'])) {
						switch($value['type']) {
							case 'escape':
								$this->ci->db->where($field, $value['value'], FALSE);
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
						$this->ci->db->where($field, NULL, FALSE);
					}
					else {
						$this->ci->db->where($field, $value , $escape);
					}
				}
			}
		}
	}

	function set_config($pack, $field, $value) {
		$value = (is_array($value))?serialize($value):$value;

		$record = array(
			'pack'=>$pack,
			'field'=>$field,
			'value'=>$value,
			'date_insert'=>date('Y-m-d H:i:s')
		);

		$this->ci->load->Model('Common_Model');
		$rs = $this->ci->Common_Model->update_config($record);
		return $rs;
	}

	function get_config($pack, $field='') {
		$this->ci->load->Model('Common_Model');

		$where['pack'] = $pack;
		if(!empty($field)) $where['field'] = $field;
		$rs = $this->ci->Common_Model->select_config($where);
		return $rs;
	}

	/**
	 * 관리 그룹별 권한
	 * @param  string $group_code 관리그룹코드
	 * @return boolean            [description]
	 */
	function check_auth_group($group_code) {
		$this->ci->load->Model('Manage_Model');
		$row = $this->ci->Manage_Model->select_group_row(array('group_code'=>$group_code));
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

	function get_code_item($group_code, $parent_code='', $return='title', $depth='') {
		$this->ci->load->model('Manage_model');

		$where = array();
		if($depth) {
			$where['depth']=$depth;
		}
		
		$items = $this->ci->Manage_model->get_code_item($group_code, $parent_code, $return,$where);
		return $items;
	}


	function get_code_info($where) {
		$this->ci->load->model('Manage_model');
		$info = $this->ci->Manage_model->select_code_row($where);
		return $info;
	}

	function get_code($where, $key='code', $value='title') {
		$this->ci->load->model('Manage_model');
		$info = $this->ci->Manage_model->select_code($where);
		foreach($info as $row) {
			$code[$row[$key]] = $row[$value];
		}
		return $code;
	}

	function get_doctor($hst_code, $biz_id='') {
		$this->ci->load->model('User_model');
		$where = array(
			'hst_code'=>$hst_code,
			'occupy_code'=>array('03-001'),
			'status'=>'1'
		);
		if($biz_id) {
			 $where["biz_id  LIKE "] = "%,{$biz_id},%";
		}

		// pre($where);
		$rs = $this->ci->User_model->get_user_all($where, 'user_id, name');
		// echo $this->ci->db->last_query();
		$doctor = array();
		foreach($rs as $row) {
			$doctor[$row['user_id']] = $row['name'];
		}

		return $doctor;
	}

	function get_cfg($cfgs) {
		$cfg_arr = (is_array($cfgs))?$cfgs:array($cfgs);
		$cfg_list = array();
		foreach($cfg_arr as $cfg) {
			switch ($cfg) {
				case 'nurse': //간호사
					$result = $this->search_user(array('occupy_code'=>'03-002', 'status'=>'1'), 'user_id, name');			// 20170309 kruddo : 재직중인 간호사만 목록화 : where 조건 추가('status'=>'1')
					break;
				case 'team': //팀
					$result = $this->get_team( '90' );
				break;
				case 'manager_team':
					$result = $this->get_team(array('90','60'));
				break;
				case 'treat_region': //진료부위
					$result = $this->get_code_item( '01' );
				break;
				case 'treat_item': //진료항목
					$result = $this->get_code_item( '02' );
				break;
				case 'treat_type': //치료구분
					$result = $this->get_code_item( '09' );
				break;
				case 'doctor': //의사
					$result = $this->get_doctor($this->ci->session->userdata('ss_hst_code'), $this->ci->session->userdata('ss_biz_id'));
				break;
				case 'path': //유입경로
					$result = $this->ci->config->item('all_path');
				break;
				case 'date': //검색날짜
					$result = get_search_type_date();
				break;
				case 'job': //직업
					$result = $this->get_code_item( '05' );
				break;
				case 'appointment_type': //예약유형
					$result = $this->ci->Manage_model->get_code_item('06', '', '', array('biz_id'=>$this->get_biz_group()));
				break;
				case 'appointment_status': //예약상태
					$result = $this->ci->Manage_model->get_code_item('07', '', '', array('biz_id'=>$this->get_biz_group()));
				break;
				case 'coordi': //예약접수자
					$result = $this->ci->User_model->get_dept_user('50');
				break;
				case 'accept': //코디팀
					$result = $this->ci->User_model->get_dept_user(array('50','90'));
					$result_add = $this->search_user(array('duty_code'=>'8'), 'user_id, name');
					foreach($result_add as $row) {
						$result[$row['user_id']]=$row['name'];
					}
				break;
				case 'skincare': //피부팀
					$result =  $this->get_user('50');
				break;
				case 'consultant': //상담팀
					//$result =  $this->get_user('90');
					$result = $this->search_user(array('dept_code'=>'90', 'status'=>'1', 'position_code'=>52), 'user_id, name');		// 20170313 kruddo : 상담팀장 목록
				break;
				case 'reg_team':		// 20170202 kruddo : 환자정보 > 상담일지 > 상담실장을 등록자(코디팀, 피부관리팀)로 변경
					$result = $this->get_team_code(array('51','50'));
				break;
				case 'card':
					$result = get_card();
				break;
				case 'bank':
					$result = get_bank();
				break;
				case 'media':
					$result = $this->get_code(array('group_code'=>'04','depth'=>'2', 'use_flag'=>'Y'));
				break;
				case 'op_type':
					$result = array('origin'=>'기본','re'=>'재수술','abroad_1'=>'해외1','abroard_2'=>'해외2','service'=>'서비스');
				break;
				case 'appointment_room': //룸(회복실/수술실)
					$result = array(
						'07-016'=>array('1'=>'회복실1','2'=>'회복실2','3'=>'회복실3','4'=>'회복실4','다'=>'다인실'),
						'07-023'=>array('1'=>'수술실1','2'=>'수술실2','3'=>'수술실3')
					);
				break;
			}

			$cfg_list[$cfg] = $result;
		}

		if(count($cfg_list)>1) {
			return $cfg_list;
		}
		else return $cfg_list[$cfg];

	}

	function get_treat_children($parent_no='0') {
		$this->ci->load->model('treat_model');
		$where = array(
			//'depth'=>$depth,
			'parent_no'=>$parent_no
		);
		$rs = $this->ci->treat_model->select_cost($where);
		return $rs;
	}

	function get_treat_row($no) {
		$this->ci->load->model('treat_model');
		$where = array(
			'no'=>$no
		);
		$row = $this->ci->treat_model->select_cost_row($where);
		return $row;
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

	function grant_menu_by_biz() {
		if(in_array($this->ci->session->userdata('ss_biz_id'), $this->ci->grant_biz)) {
			$biz_id = $this->ci->session->userdata('ss_biz_id');
		}
		else {
			$biz_id = 'none';
		}
		return $biz_id;
	}

	function delete_file($file_no) {
		$this->ci->load->model('Common_Model');
		$file_info = $this->ci->Common_Model->select_file_row(array('seqno'=>$file_no));
		if($file_info) {
			$rs = $this->ci->Common_Model->delete_file(array('seqno'=>$file_no));
			if($rs) {
				@unlink($_SERVER['DOCUMENT_ROOT'].$file_info['file_path']);
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

}
?>
