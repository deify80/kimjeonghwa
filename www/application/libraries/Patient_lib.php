<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Patient_lib {
	private $ci;

	function __construct() {
		$this->ci =& get_instance();
	}

	//진료항목 변환
	function set_treat($treat_info, $type='array', $style='long') {
		if(is_null($treat_info)) return false;

		if(empty($treat_info)) return false;

		$treat_info = explode(',',$treat_info);


		foreach($treat_info as $treat) {
			if($style == 'long') {
				$treat_list[$treat] = $this->treat_nav($treat,'>');
			}
			else {
				$treat_list[$treat] = $this->treat_name($treat,'>');
			}

		}
		if($type == 'text') {
			return implode(' , ',$treat_list);
		}
		else return $treat_list;
	}

	function treat_name($no) {
		$this->ci->load->model('treat_model');
		$where = array(
			'no'=>$no
		);
		$row = $this->ci->treat_model->select_cost_row(array('no'=>$where));
		$name = $row['name'];

		return $name;
	}

	function treat_info($no) {
		$this->ci->load->model('treat_model');
		$where = array(
			'no'=>$no
		);
		$row = $this->ci->treat_model->select_cost_row(array('no'=>$where));
		//echo $this->ci->db->last_query();
		return $row;
	}

	/**
	 * 진료부위 네비게이션 생성
	 * @param  int $no 소분류(3차depth) PK
	 * @return array
	 */
	function treat_nav($no, $glue='') {
		$this->ci->load->model('treat_model');
		$where = array(
			'no'=>$no
		);
		$row = $this->ci->treat_model->select_cost_row($where);
		$route_arr = explode('_',$row['route']);
		$rs = $this->ci->treat_model->select_cost(array('no'=>$route_arr));
		foreach($rs as $row) {
			$nav[] = $row['name'];
		}

		if($glue) {
			return implode($glue,$nav);
		}
		return $nav;
	}

	function treat_children($no) {
		$this->ci->load->model('treat_model');
		$rs = $this->ci->treat_model->select_cost_children($no);
		//echo $this->ci->db->last_query();

		$children = array_filter(explode(',',$rs));
		return implode(',',$children);
	}

	//환자정보가공
	function set_patient($patient_info) {
		$doctor = $this->ci->common_lib->get_doctor($this->ci->session->userdata('ss_hst_code'), $this->ci->session->userdata('ss_biz_id'));//의사
		$job = $this->ci->common_lib->get_code_item( '05' ); //직업
		$manager = $this->ci->common_lib->get_user($patient_info['manager_team_code']);

		//사진
		$default_photo = '/images/common/no_photo_user.png';
		$photo = (file_exists($_SERVER['DOCUMENT_ROOT'].$patient_info['photo']) && $patient_info['photo'])?$patient_info['photo']:$default_photo;
		$patient_info['photo_path'] = $photo;

		//나이
		if($patient_info['birth']!='0000-00-00') {
			$age = date('Y')-substr($patient_info['birth'],0,4)+1;
		}
		else $age = '';
		$patient_info['age'] = $age;

		$patient_info['job'] = $job[$patient_info['job_code']]; //직업
		$patient_info['doctor_name'] = $doctor[$patient_info['doctor_id']]; //담당의사
		$patient_info['manager_name'] = $manager[$patient_info['manager_id']]; //상담
		$patient_info['mobile'] = format_mobile($patient_info['mobile']);

		if($patient_info['treat_cost_no']) {
			$treat_nav = $this->treat_nav($patient_info['treat_cost_no']);
			$patient_info['treat_nav'] = implode(' &gt ', $treat_nav);
		}
		else {
			$patient_info['treat_nav'] = '미지정';
		}


		//개인정보보호
		$patient_info['mobile'] = $this->ci->common_lib->protect_patient($patient_info['mobile'], $patient_info['manager_team_code'], 'ex_phone','mobile'); //모바일
		//$patient_info['tel'] = $this->ci->common_lib->protect_patient($patient_info['tel'], $patient_info['manager_team_code'], 'ex_phone','tel'); //유선전화
		//$patient_info['messenger'] = $this->ci->common_lib->protect_patient($patient_info['tel'], $patient_info['manager_team_code'], 'ex_phone','tel'); //메신저
		//$patient_info['jumin'] = $this->ci->common_lib->protect_patient($patient_info['jumin'], $patient_info['manager_team_code'], 'ex_phone','jumin'); //주민등록번호
		//$patient_info['email'] = $this->ci->common_lib->protect_patient($patient_info['email'], $patient_info['manager_team_code'], 'ex_phone','email'); //이메일
		//$patient_info['address'] = $this->ci->common_lib->protect_patient($patient_info['address'], $patient_info['manager_team_code'], 'ex_phone','address'); //주소

		return $patient_info;
	}

	function expect_grade($sync=false) {
		$this->ci->load->model('Patient_Model');
		$grade_list = $this->ci->Patient_Model->select_patient_all(array('is_use'=>'Y'), 'patient_grade','no, standard','standard DESC');
		$max_amount = 0;

		foreach($grade_list as $k => $grade) {
			if($k>0) {
				$where = array(
					'amount_paid >= '=>$grade['standard'],
					'amount_paid < '=>$max_amount);

			}
			else {
				$where = array(
					'amount_paid >= '=>$grade['standard']
				);
			}

			$where['biz_id'] = $this->ci->session->userdata('ss_biz_id');

			if($sync) {
				$record = array('grade_no'=>$grade['no']);
				$rs = $this->ci->Patient_Model->update_patient($record, $where);
				if(!$rs) {
					break;
				}
			}
			else {
				$cnt = $this->ci->Patient_Model->count_patient($where);
				$rs[$grade['no']] = $cnt;
			}
			$max_amount = $grade['standard'];

		}
		return $rs;
	}


	function remove_patient_info($patient_no) {
		$CI = $this->ci;
		$this->ci->load->model('Patient_Model');
		//동의서
		//예약
		//차트
		//컴플레인
		//상담
		//즤사
		//
	}
}
