<?php
/**
 * 환자관리
 * 작성 : 2015.05.26
 * @author 이혜진
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Patient extends CI_Controller {

	var $dataset;
	public function __construct() {
		session_start();
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;

		$this->load->model( array (
			'Patient_Model'
		) );

		$this->load->library('Patient_lib');
	}

	/**
	 * 환자 목록 wrap
	 * @return [type] [description]
	 */
	public function lists() {
		$search = $this->session->userdata('search');
		$datum = array(
			'auth'=>array(
				'patient_delete'=>$this->common_lib->check_auth_group('patient_delete'),
				'favorite_pt'=>$this->common_lib->check_auth_group('favorite_pt')
			),
			'cfg'=>$this->common_lib->get_cfg(array('team','treat_region','doctor','path','date')),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:1,
		);

		$this->_render('patient_lists', $datum );
	}

	/**
	 * 환자목록 페이징
	 * @return [type] [description]
	 */
	function patient_list_paging() {
		$p = $this->param;

		parse_str($this->input->post('search'), $assoc);
		$this->session->set_userdata('search',array_filter($assoc)); //검색데이터 세션처리

		$type = $p['type']; //share:공동DB
		$page = $assoc['page'];
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		$where = array();
		$where_offset = array(
			'is_delete'=>'N',
			'biz_id' => $this->session->userdata('ss_biz_id'),
			'hst_code'=> $this->session->userdata('ss_hst_code')
		);

		$consulting_link=$this->common_lib->check_auth_group('consulting_link');				// 20170216 kruddo : CRM > 환자정보 : 권한없는 사람 고객상세정보보기 막기

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( name LIKE '%{$v}%' OR mobile LIKE '%{$v}%' OR chart_no LIKE '%{$v}%' )"]=NULL;
				break;
				case 'date_s':
					$where['date_insert >=']="{$v}";
				break;
				case 'date_e':
					$where['date_insert <=']="{$v}";
				break;
				case 'appointment_date_s':
					$where['appointment_last >=']="{$v}";
				break;
				case 'appointment_date_e':
					$where['appointment_last <=']="{$v}";
				break;
				case 'favorite':
					$user_no = $this->session->userdata("ss_user_no");
					$where["CONCAT(',',favorite_user,',') LIKE '%,{$user_no},%'"] = NULL;
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$auth = $this->common_lib->check_auth_group('info_patient');
		if(!$auth) {
			$where_offset['manager_team_code'] = $this->session->userdata('ss_team_code');
		}

		$doctor = $this->common_lib->get_cfg('doctor');
		$manager = $this->common_lib->get_user();
		//$treat_region = $this->common_lib->get_cfg('treat_region');
		//$treat_item = $this->common_lib->get_cfg('treat_item');
		$grade_list = $rs = $this->Patient_Model->select_patient_all(array('is_use'=>'Y'),'patient_grade','code, rgb, name','','code');



		$rs = $this->Patient_Model->select_patient_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['date_insert'] = substr($row['date_insert'],0,10);

				// $row['treat_info'] = ($treat_region[$row['treat_region_code']])?$treat_region[$row['treat_region_code']].' > '.$treat_item[$row['treat_item_code']]:''; //진료정보
				//$row['treat_region_name'] = $treat_region[$row['treat_region_code']];
				//$row['treat_item_name'] = $treat_item[$row['treat_item_code']];
				//
				$row['treat_nav'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
				//나이
				if($row['birth']!='0000-00-00') {
					$age = date('Y')-substr($row['birth'],0,4)+1;
				}
				else $age = '0';
				$row['age'] = $age;

				$row['grade'] = $grade_list[$row['grade_code']];

				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);

				$favorite_arr = explode(',',$row['favorite_user']);
				$row['is_favorite'] = (in_array($this->session->userdata('ss_user_no'), $favorite_arr))?'Y':'N';
				$row['idx'] = $idx;

				$row['consulting_link']=$consulting_link;				// 20170216 kruddo : CRM > 환자정보 : 권한없는 사람 고객상세정보보기 막기

				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$user_no = $this->session->userdata('ss_user_no');
		$rs['count']['favorite'] = $this->Patient_Model->count_patient(array("CONCAT(',',favorite_user,',') LIKE '%,{$user_no},%'"=>NULL));


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	/**
	 * 수술목록
	 *
	 * @return void
	 */
	public function project() {
		$search = $this->session->userdata('search');
		$datum = array(
			'auth'=>array(
				'patient_delete'=>$this->common_lib->check_auth_group('patient_delete'),
				'favorite_pt'=>$this->common_lib->check_auth_group('favorite_pt')
			),
			'cfg'=>$this->common_lib->get_cfg(array('team','treat_region','doctor','path','date')),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:1,
		);

		$this->_render('project_lists', $datum );
	}

	public function project_list_paging() {
		$p = $this->param;

		parse_str($this->input->post('search'), $assoc);
		$this->session->set_userdata('search',array_filter($assoc)); //검색데이터 세션처리

		$type = $p['type']; //share:공동DB
		$page = $p['page'];
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		$where = array();
		$where_offset = array(
			'pp.type'=>'수술',
			'p.is_delete'=>'N',
			'p.biz_id' => $this->session->userdata('ss_biz_id'),
			'p.hst_code'=> $this->session->userdata('ss_hst_code')
		);

		$consulting_link=$this->common_lib->check_auth_group('consulting_link');				// 20170216 kruddo : CRM > 환자정보 : 권한없는 사람 고객상세정보보기 막기

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( name LIKE '%{$v}%' OR mobile LIKE '%{$v}%' OR chart_no LIKE '%{$v}%' )"]=NULL;
				break;
				case 'date_s':
					$where['pp.date_project >=']="{$v}";
				break;
				case 'date_e':
					$where['pp.date_project <=']="{$v}";
				break;
				case 'manager_team_code':
				case 'manager_id':
					$where['pp.'.$k]="{$v}";
				break;

				case 'treat_cost_no':
					$treat_route = $this->patient_lib->treat_children($v);
					$treat_route_arr = explode(',',$treat_route);
					if(is_array($treat_route_arr)) {
						$tmp_arr = array();
						foreach($treat_route_arr as $tno) {
							$tmp_arr[] = "CONCAT(',',pp.treat_costs,',') LIKE '%{$tno}%'";
						}
						$tmp = "(".implode(' OR ', $tmp_arr).")";

						$where[$tmp]=NULL;
					}
					
				break;
				case 'doctor_id':
					$where['pp.doctor_id']="{$v}";
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$auth = $this->common_lib->check_auth_group('info_patient');
		if(!$auth) {
			$where_offset['manager_team_code'] = $this->session->userdata('ss_team_code');
		}

		$doctor = $this->common_lib->get_cfg('doctor');
		$manager = $this->common_lib->get_user();

		// pre($where);

		$rs = $this->Patient_Model->select_project_paging($where, $offset, $limit, $where_offset);
		
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['manager_name'] = $manager[$row['manager_id']];

				// $row['treat_info'] = ($treat_region[$row['treat_region_code']])?$treat_region[$row['treat_region_code']].' > '.$treat_item[$row['treat_item_code']]:''; //진료정보
				//$row['treat_region_name'] = $treat_region[$row['treat_region_code']];
				//$row['treat_item_name'] = $treat_item[$row['treat_item_code']];
				
				$treat_costs_arr = array_filter(explode(',',$row['treat_costs']));
				$treat_nav = array();
				if(!empty($treat_costs_arr)) {
					
					foreach($treat_costs_arr  as $tno) {
						$treat_nav[] = $this->patient_lib->treat_nav($tno, ' &gt ');
					}
					
				}
				
				$row['treat_nav'] = implode('<br>',$treat_nav);

				//나이
				if($row['birth']!='0000-00-00') {
					$age = date('Y')-substr($row['birth'],0,4)+1;
				}
				else $age = '0';
				$row['age'] = $age;

				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);

				$favorite_arr = explode(',',$row['favorite_user']);
				$row['idx'] = $idx;

				$row['consulting_link']=$consulting_link;				// 20170216 kruddo : CRM > 환자정보 : 권한없는 사람 고객상세정보보기 막기

				$list[] = $row;
				$idx--;
			}
		}

		//pre($list);


		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$user_no = $this->session->userdata('ss_user_no');
		$rs['count']['favorite'] = $this->Patient_Model->count_patient(array("CONCAT(',',favorite_user,',') LIKE '%,{$user_no},%'"=>NULL));


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	/**
	 * 환자 등록/수정
	 * @return [type] [description]
	 */
	public function input() {
		$no = $this->param['patient_no'];
		$referer = $this->param['referer'];
		if($no) {
			$mode = 'update';
			$rs = $this->Patient_Model->select_patient_row(array('no'=>$no));

			if($rs['introducer_no'] > 0) {
				$introducer = $this->Patient_Model->select_patient_row(array('no'=>$rs['introducer_no']));
				$rs['introducer_name']=$introducer['name'];//소개자이름
			}

			$auth_manage = ($this->common_lib->check_auth_group('info_patient') || $rs['manager_team_code']==$this->session->userdata('ss_team_code'))?true:false;
		}
		else {
			$mode = 'insert';
			$rs = array(
				// 'sex'=>'F',
				'grade_type'=>'N',
				'is_o'=>'N'
			);

			if($this->session->userdata('ss_dept_code')=='90') {
				$rs['manager_team_code'] = $this->session->userdata('ss_team_code');
				$rs['manager_id'] = $this->session->userdata('ss_user_id');
			}
			$auth_manage = true;
		}

		$cfg = $this->common_lib->get_cfg(array('doctor','job','manager_team','path','treat_region'));


		$patient_datum = array(
			'cfg'=>$cfg,
			'patient'=>$this->patient_lib->set_patient($rs),
			'auth'=>array(
				'consulting'=>($this->session->userdata('ss_dept_code')=='90')?true:false,
				'manage'=>$auth_manage,
				'ex_phone'=>$this->common_lib->check_auth_group('ex_phone'),
			)
		);

		//개인정보 업데이트 권한
		$patient_datum['auth']['update'] = ($rs['manager_team_code']==$this->session->userdata('ss_team_code') || $patient_datum['auth']['ex_phone'] || !$patient_datum['patient']['no'])?true:false;

		$form_patient = $this->layout_lib->fetch_('/hospital/patient/patient_form.html', $patient_datum);

		//환자관리 권한
		$datum = array(
			'mode'=>$mode,
			'referer'=>$referer,
			'cfg'=>array_merge($cfg, array('callback'=>$callback)),
			'patient'=>$rs,
			'inc'=>array(
				'patient'=>$form_patient
			),
			'rs'=>$rs
		);

		$this->_render('patient_input', $datum, 'inc');
	}

	public function patient_list() {
		$p = $this->input->post(NULL, true);

		$search_word = $p['search_word'];
		$field = ($p['field'])?$p['field']:'*';

		$where['(name LIKE "%'.$search_word.'%" OR messenger LIKE "%'.$search_word.'%" OR mobile LIKE "%'.$search_word.'%")'] = null;
		$where['is_delete'] = 'N';
		$where['biz_id'] = $this->session->userdata('ss_biz_id');
		$where['hst_code'] = $this->session->userdata('ss_hst_code');
		// pre($where);
		if(!$this->common_lib->check_auth_group('info_patient')) {
			$where['manager_team_code'] = $this->session->userdata('ss_team_code');
		}

		$rs = $this->Patient_Model->select_patient_all($where,'patient',$field);
		// echo $this->db->last_query();

		if($rs) {
			$result = array();
			foreach($rs as $row) {
				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);
				$row['search'] = implode(',', array_filter(array($row['mobile'], $row['messenger'])));
				$result[] = $row;
			}
			return_json(true,'', $result);
		}
		else {
			return_json(false);
		}
	}

	/**
	 * 회원등급목록
	 * @return [type] [description]
	 */
	public function settings_grade() {
		$datum = array(

		);
		$this->_render('settings_grade', $datum );
	}


	/**
	 * 회원등급 등록/수정
	 * @return [type] [description]
	 */
	public function settings_grade_input() {
		$grade_no = $this->param['no'];
		if($grade_no>0) {
			$grade_info = $this->Patient_Model->select_patient_row(array('no'=>$grade_no), 'patient_grade');
		}
		else {
			$grade_info = array(
				'is_fix'=>'N',
				'is_use'=>'Y',
				'rgb'=>'#8BC34A'
			);
		}

		$datum = array(
			'rs'=>$grade_info
		);
		$this->_display('settings_grade_input', $datum );
	}

	function grade_list() {

		$grade_cnt = $this->Patient_Model->count_patient_grade(array('biz_id'=>$this->session->userdata('ss_biz_id')));
		// echo $this->db->last_query();
		$expect_cnt = $this->patient_lib->expect_grade();
		// pre($grade_cnt[1]);

		$rs = $this->Patient_Model->select_patient_all($where,'patient_grade','*','standard DESC');

		$list = array();
		foreach($rs as $row) {
			$row['cnt_expect'] = number_format($expect_cnt[$row['no']]);
			$row['cnt'] = number_format($grade_cnt[$row['no']]['cnt']);
			$list[] = $row;
		}
		if($rs) {
			return_json(true,'', $list);
		}
		else {
			return_json(false);
		}
	}

	/**
	 * 환자상세 화면 설정
	 * @return [type] [description]
	 */
	public function settings_widget() {

		// $widget = $this->_get_settings_widget($this->session->userdata('ss_user_no'));
		$datum = array(
			// 'widget'=>$widget
		);
		$this->_display('settings_widget', $datum );
	}

	/**
	 * 환자 상세정보
	 * @return [type] [description]
	 */
	public function info() {

		$patient_no = $this->uri->segment(3);
		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));



		//환자 상세 정보 보기 권한 체크
		if($patient_info['is_delete']=='Y') {
			$auth = false;
		}
		else {
			$auth = $this->common_lib->check_auth_group('info_patient');
		}

		// $auth_info = false;
		if($auth) {
			if($this->session->userdata('ss_biz_id') != $patient_info['biz_id']) {
				//청담여신, 청담여신(중국) cross 권한 부여
				if(in_array($this->session->userdata('ss_biz_id'), array('ezham','ezham_cn')) && in_array($patient_info['biz_id'], array('ezham','ezham_cn'))) {
					$auth_info = true;
				}
				else {
					$auth_info = false;
				}
			}
			else {
				$auth_info = true;
			}
		}
		else {
			if($this->session->userdata('ss_team_code') == $patient_info['manager_team_code']) {
				$auth_info = true;
			}
			else {
				$auth_info = false;
			}
		}

		$status = $this->common_lib->get_cfg('appointment_status');
		$datum = array(
			'cfg'=>array(
				'status'=>$status,
			),
			'auth'=>array(
				'info'=>$auth_info, //(!$this->common_lib->check_auth_group('info_patient') && $this->session->userdata('ss_team_code') != $patient_info['manager_team_code'])?false:true,
				'chart_normal'=>$this->common_lib->check_auth_group('tab_chart_normal'),
				'chart_checkup'=>$this->common_lib->check_auth_group('tab_chart_checkup'),
				'pay'=>$this->common_lib->check_auth_group('tab_pay'),
				'doctor'=>$this->common_lib->check_auth_group('tab_doctor'),
				'nurse'=>$this->common_lib->check_auth_group('tab_nurse'),
				'treat'=>$this->common_lib->check_auth_group('tab_treat'),
				'consulting'=>$this->common_lib->check_auth_group('tab_consulting'),
				'photo'=>$this->common_lib->check_auth_group('tab_photo'),
				'material'=>$this->common_lib->check_auth_group('tab_material'),
				'agree'=>$this->common_lib->check_auth_group('tab_agree'),
				'complain'=>$this->common_lib->check_auth_group('tab_complain')
			),
			'basic'=>$patient_info
		);

		// pre($datum);
		$this->_render('info', $datum );

	}

	public function info_tab() {
		$this->patient_no = $this->param['patient_no'];
		$this->project_no = $this->param['project_no'];
		$tab = $this->param['tab'];

		switch($tab) {
			case 'chart_normal': //일반차트
				$this->info_widget_chart('normal');
				break;
			case 'chart_checkup': //검사차트
				$this->info_widget_chart('checkup');
				break;
			case 'pay': //수납내역
				$this->info_widget_pay($patient_no);
				break;
			case 'doctor': //Dr's Order
				$this->info_widget_doctor($patient_no);
				break;
			case 'nurse': //수술간호일지
				$this->info_widget_nurse($patient_no);
				break;
			case 'treat': //치료일지
				$this->info_widget_treat($patient_no);
				break;
			case 'skin': //피부관리일지
				$this->info_widget_skin($patient_no);
				break;
			case 'consulting': //상담일지
				$this->info_widget_consulting($patient_no);
				break;
			case 'photo_normal': //전후사진비교
				$this->info_widget_photo('normal');
				break;
			case 'material': //물품기록지
				$this->info_widget_material();
				break;
			case 'agree': //동의서
				$this->info_widget_agree();
				break;
			case 'chart_cst': //상담차트
				$this->info_widget_chart('cst');
				break;
			case 'photo_cst': //상담사진
				$this->info_widget_photo('cst');
				break;
			case 'complain': //컴플레인일지
				$this->info_widget_complain($patient_no);
				break;
		}
	}

	public function info_project() {
		$patient_no = $this->param['patient_no'];


		$doctor = $this->common_lib->get_cfg('doctor');
		$project_rs = $this->Patient_Model->select_patient_all(array('patient_no'=>$patient_no, 'is_delete'=>'N'), 'patient_project','','date_project DESC');

		$project_list = array();
		foreach($project_rs as $row) {

			$row['doctor_name'] = $doctor[$row['doctor_id']];
			$treat_costs_arr = array_filter(explode(',',$row['treat_costs']));
			$treat_costs_count = count($treat_costs_arr);
			$treat_nav = array();
			if(empty($treat_costs_arr)) {
				$treat_nav[] = '미지정';
			}
			else {
				
				foreach($treat_costs_arr as $tno) {
					$treat_nav[] = $this->patient_lib->set_treat($tno,'text', 'long');
					//$treat_short = array_shift(explode('>',$treat_nav));
				}
				
			//	$treat_short = '';
			}
			
			/*
			$treat_costs_first = array_pop($treat_costs_arr);
			if($treat_costs_first) {
				$treat_nav = $this->patient_lib->set_treat($treat_costs_first,'text', 'long');
				$treat_short = array_shift(explode('>',$treat_nav));
			}
			else {
				$treat_nav = '미지정';
				$treat_short = '';
			}
			*/
			
			
			//$row['treat_info'] = ($row['type']=='진료')?'일반진료':$treat_short;

			$row['treat_nav'] = $treat_nav;
			$row['treat_count'] = $treat_costs_count;
			$project_list[] = $row;
		}

		$datum = array(
			'cfg'=>$this->common_lib->get_cfg(array('treat_region','treat_item')),
			'patient_no'=>$patient_no,
			'auth'=>array(
				'project_delete'=>$this->common_lib->check_auth_group('project_delete')
			),
			'project'=>$project_list
		);

		// pre($datum);
		$this->_render('info_project', $datum, 'inc');
	}

	public function info_project_input() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		if($project_no) {
			$mode = 'update';
			$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project', '*, (type+0) AS type_idx');
			$project_info['nurse_list'] = explode(',',$project_info['nurse_id']);
			$project_info['treat_nav'] = $this->patient_lib->set_treat($project_info['treat_cost_no'],'text');

			$treat_costs_arr = explode(',',$project_info['treat_costs']);
			$treat_info = array();
			if(is_array($treat_costs_arr)) {
				foreach($treat_costs_arr as $treat_no) {
					if($treat_no <1) continue;
					
					$treat_info[$treat_no] =  $this->patient_lib->treat_info($treat_no);
					$treat_info[$treat_no]['nav'] = $this->patient_lib->set_treat($treat_no, 'text');
					$json_code = array(
						'abroad_1'=>$treat_info[$treat_no]['cost_abroad_1'],
						'abroad_2'=>$treat_info[$treat_no]['cost_abroad_2'],
						're'=>$treat_info[$treat_no]['cost_abroad_re'],
						'origin'=>$treat_info[$treat_no]['cost_origin'],

					);
					$treat_info[$treat_no]['json'] = json_encode(array('no'=>$treat_no,'cost'=>$json_code));
					
				}
			}
			$project_info['treat_info'] = $treat_info;
		}
		else {
			$mode = 'insert';
			$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));
			$project_info = array(
				'patient_no'=>$patient_no,
				'doctor_id'=>$patient_info['doctor_id'],
				'manager_team_code'=>$patient_info['manager_team_code'],
				'manager_id'=>$patient_info['manager_id'],
				'amount_refund'=>0,
				'op_type'=>'origin',
				'tax_type'=>'tax',
				'type_idx'=>'1',
				'amount_basic'=>0,
				'date_project'=>TODAY
			);
		}


		// pre($project_info);
		$datum = array(
			'mode'=>$mode,
			'cfg'=>$this->common_lib->get_cfg(array('doctor','manager_team','treat_region', 'nurse')),
			'rs'=>$project_info
		);
		$this->_render('info_project_input',$datum,'inc');
	}

	public function info_widget() {
		$patient_no = $this->param['patient_no'];
		$datum = array(
			'basic'=>array(
				'no'=>$patient_no
			)
		);
		$this->_display('info_widget', $datum );
	}

	/**
	 * 환자정보 > 기본정보
	 * @return [type] [description]
	 */
	public function info_widget_basic() {
		$patient_no = $this->param['patient_no'];
		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));

		$patient_info = $this->patient_lib->set_patient($patient_info);
		$grade_list = $rs = $this->Patient_Model->select_patient_all(array('is_use'=>'Y'),'patient_grade','no, code, rgb, name','','no');

		$datum = array(
			'basic'=>$patient_info,
			'grade'=>$grade_list[$patient_info['grade_no']],
			'auth'=>array(
				'team_change'=>$this->common_lib->check_auth_group('team_change'),
				'consulting_link'=>$this->common_lib->check_auth_group('consulting_link')
			)
		);
		$this->_render('info_widget_basic', $datum, 'inc');
	}

	/**
	 * 환자정보 > 기본정보 > 사진등록
	 * @return [type] [description]
	 */
	public function info_widget_basic_photo() {
		$patient_no = $this->param['patient_no'];
		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));
		$datum = array(
			'basic'=>$patient_info
		);
		$this->_display('info_widget_basic_photo', $datum );
	}

	/**
	 * 환자정보 > 예약정보
	 * @return [type] [description]
	 */
	function info_widget_appointment() {
		$page = $this->input->post('page');
		$limit = 5; //($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;
		$patient_no = $this->param['patient_no'];

		//검색조건설정
		$where = array('patient_no'=>$patient_no);
		foreach($this->param['search'] as $search) {
			$v = $search['value'];
			$k = $search['name'];
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				default :
					$where[$k] = $v;
					break;
			}
		}

		$this->load->Model('Manage_Model');
		$doctor = $this->common_lib->get_cfg('doctor');
		$manager = $this->common_lib->get_user();
		$status = $this->common_lib->get_cfg('appointment_status');
		$type = $this->common_lib->get_cfg('appointment_type');

		$rs = $this->Patient_Model->select_widget_paging('patient_appointment',$where,$offset, $limit, 'appointment_date DESC');

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['status_text'] = $status[$row['status_code']];
				$row['type_text'] = $type[$row['type_code']];

				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['appointment_time'] = substr($row['appointment_time_start'],0,5).'~'.substr($row['appointment_time_end'],0,5);
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();
		$paging['this'] = $page;
		//
		$datum = array(
			'count'=>$rs['count'],
			'auth'=>array(
				'appointment_delete'=>$this->common_lib->check_auth_group('appointment_delete')
			),
			'list'=>$list,
			'paging'=>$paging
		);

		$this->_display('info_widget_appointment', $datum );
	}

	/**
	 * 예약 수정
	 * @return [type] [description]
	 */
	public function info_widget_appointment_input() {
		$patient_no = $this->param['patient_no'];
		$appointment_no = $this->param['no'];

		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));
		$row['manager_name'] = $manager[$row['manager_id']];


		$this->load->model('Manage_Model');
		$team = $this->common_lib->get_cfg('team');
		$treat_region = $this->common_lib->get_cfg('treat_region'); //진료부위
		$type_origin = $this->common_lib->get_cfg('appointment_type');
		$doctor = $this->common_lib->get_cfg('doctor');//의사
		$status = $this->common_lib->get_cfg('appointment_status');
		$user_coordi = $this->common_lib->get_cfg('coordi'); //코디팀직원
		$user_skincare = $this->common_lib->get_cfg('skincare'); //피부팀직원
		$room = $this->common_lib->get_cfg('appointment_room'); //회복실/수술실

		$appointment_type_dr = $this->common_lib->check_auth_group('appointment_type_dr');
		$type_dr = array('06-001','06-002','06-003','06-012'); //Dr.P, Dr.J, Dr.S, Dr.T
		$type = $type_origin;
		if(!$appointment_type_dr) {
			foreach($type_dr as $v) {
				unset($type[$v]); //Dr.P
			}
		}

		if($appointment_no > 0) {
			$mode = 'update';

			$appointment_info = $this->Patient_Model->select_appointment_row(array('pa.no'=>$appointment_no));

			$appointment_info['treat_info'] = $this->patient_lib->set_treat($appointment_info['treat_info']);

			$appointment_info['type_code_txt'] = $type_origin[$appointment_info['type_code']];

			$auth_manage = ($this->common_lib->check_auth_group('info_patient') || $appointment_info['manager_team_code']==$this->session->userdata('ss_team_code'))?true:false;

			//수술접근
			//echo $appointment_info['type_code'];
			if(!$appointment_type_dr && in_array($appointment_info['type_code'], $type_dr)) $auth_manage = false;

		}
		else {
			$auth_manage = true;
			$mode = 'insert';
			$appointment_info = array(
				'patient_no'=>$patient_no,
				'visit_code'=>'1',
				'manager_team_code'=>$patient_info['manager_team_code'],
				'manager_id'=>$patient_info['manager_id'],
				'appointment_date'=>date('Y-m-d'),
				'method_code'=>'1'
			);
		}


		$appointment_datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'team'=>$team,
				'treat_region'=>$treat_region,
				'type'=>$type,
				'doctor'=>$doctor,
				'acceptor'=>$user_coordi,
				'skincare'=>$user_skincare,
				'status'=>$status,
				'time'=>array(
					'start'=>strtotime($appointment_info['appointment_date'].' '.APPOINTMENT_START),
					'end'=>strtotime($appointment_info['appointment_date'].' '.APPOINTMENT_END)
				),
				'room'=>$room[$appointment_info['status_code']]
			),
			'rs'=>$appointment_info,
			'auth'=>array(
				'manage'=> $auth_manage,
				'appointment_type_dr'=>$this->common_lib->check_auth_group('appointment_type_dr')
			)
		);
		// $form_appointment = $this->load->view('/hospital/treat/appointment_form', $appointment_datum, true);
		$form_appointment = $this->layout_lib->fetch_('/hospital/treat/appointment_form.html', $appointment_datum);

		$datum = array(
			'mode'=>$mode,
			'inc'=>array('appointment'=>$form_appointment),
			'auth'=>array(
				'manage'=> $auth_manage
			)
		);
		$this->_render('info_widget_appointment_input', $datum, 'inc');
	}



	public function get_room() {
		$code = $this->param['code'];
		$rooms = $this->common_lib->get_cfg('appointment_room');
		return_json(true, '', $rooms[$code]);
	}


	/**
	 * 환자정보 > 상담일지
	 * @return [type] [description]
	 */
	function info_widget_consulting() {
		$p = $this->param;
		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;
		$patient_no = $p['patient_no'];
		$search = $p['search'];


		if(empty($search)) {
			$search = array(
				'method' => 'all'
			);
		}

		//검색조건설정
		$where = array('patient_no'=>$patient_no);
		foreach($search as $k=>$v) {
			if($v == 'all' || (!$v && $v!=='0')) continue;
			switch($k) {
				default :
					$where[$k] = $v;
					break;
			}
		}

		$manager = $this->common_lib->get_user();
		$rs = $this->Patient_Model->select_widget_paging('patient_consulting', $where, $offset, $limit, 'date_consulting DESC, no DESC' );

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$datum = array(
			'tab'=>'consulting',
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'search'=>$search,
			'cfg'=>array(
				'search'=>array(
					'all'=>'전체',
					'전화'=>'전화',
					'방문'=>'방문'
					//,
					//'온라인'=>'온라인'			// 20170203 kruddo : 상담일지 등록 > 상담방식 > 온라인 제외
				)
			),
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete')
			)
		);

		$this->_render('info_widget_consulting', $datum, 'inc');
	}

	/**
	 * 상담일지 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_consulting_input() {
		$p = $this->param;
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];
		$consulting_no = $p['no'];

		if($consulting_no > 0) {
			$mode = 'update';
			$consulting_info = $this->Patient_Model->select_widget_row('patient_consulting', array('no'=>$consulting_no), '*, (method+0) as method_code');
			$consulting_info['treat_info'] = $this->patient_lib->set_treat($consulting_info['treat_info']);
		}
		else {
			$mode = 'insert';
			$project_info = $this->Patient_Model->select_patient_row(array('no'=>$this->param['project_no']), 'patient_project');
			$consulting_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'manager_team_code'=>$project_info['manager_team_code'],
				'manager_id'=>$project_info['manager_id'],
				'date_consulting'=>date('Y-m-d'),
				'method_code'=>'1'
			);
		}

		$datum = array(
			'mode'=>$mode,
			//'cfg'=>$this->common_lib->get_cfg(array('manager_team','treat_region')),				// 20170202 kruddo : 간호팀, 상담팀 대신 코디팀, 피부관리팀으로 변경
			'cfg'=>$this->common_lib->get_cfg(array('reg_team','treat_region')),
			'rs'=>$consulting_info
		);
		// pre($datum);
		$this->_render('info_widget_consulting_input', $datum, 'inc');
	}

	/**
	 * 환자정보 > 치료일지
	 * @return [type] [description]
	 */
	public function info_widget_treat() {
		$p = $this->param;
		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];

		//검색조건설정
		$where = array('patient_no'=>$patient_no); //, 'project_no'=>$project_no

		$manager = $this->common_lib->get_user();
		$nurse = $this->common_lib->get_cfg('nurse');

		$rs = $this->Patient_Model->select_widget_paging('patient_treat', $where, $offset, $limit, 'date_treat DESC' );

		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['doctor_name'] = $manager[$row['doctor_id']];
				$row['nurse_name'] = $nurse[$row['nurse_id']]['name'];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$datum = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete')
			)
		);

		$this->_render("info_widget_treat", $datum,'inc');
	}

	/**
	 * 환자정보 > 치료일지 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_treat_input() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$treat_no = $this->param['no'];

		if($treat_no > 0) {
			$mode = "update";
			$treat_info = $this->Patient_Model->select_widget_row('patient_treat', array('no'=>$treat_no));
			$treat_info['treat_info'] = $this->patient_lib->set_treat($treat_info['treat_info']);
		}
		else {
			$mode = 'insert';
			$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project');
			$treat_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'doctor_id'=>$project_info['doctor_id'],
				'date_treat'=>date('Y-m-d')
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>$this->common_lib->get_cfg(array('doctor','skincare','nurse','treat_region')),
			'rs'=>$treat_info
		);
		$this->_render('info_widget_treat_input', $datum, 'inc');
	}

	/**
	 * 피부관리일지
	 * @return [type] [description]
	 */
	public function info_widget_skin() {
		$p = $this->param;
		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];

		//검색조건설정
		$where = array('patient_no'=>$patient_no, 'project_no'=>$project_no);

		$manager = $this->common_lib->get_user();
		// $nurse = $this->common_lib->get_cfg('nurse');

		$rs = $this->Patient_Model->select_widget_paging('patient_skin', $where, $offset, $limit, 'date_skincare DESC' );

		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['doctor_name'] = $manager[$row['doctor_id']];
				$row['skincare_name'] = $manager[$row['skincare_id']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$datum = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete')
			)
		);

		$this->_render("info_widget_skin", $datum,'inc');
	}

	/**
	 * 환자정보 > 피부관리일지 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_skin_input() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$skin_no = $this->param['no'];

		if($skin_no > 0) {
			$mode = "update";
			$skin_info = $this->Patient_Model->select_widget_row('patient_skin', array('no'=>$skin_no));
			$skin_info['treat_info'] = $this->patient_lib->set_treat($skin_info['treat_info']);
		}
		else {
			$mode = 'insert';
			$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project');
			$skin_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'doctor_id'=>$project_info['doctor_id'],
				'manager_team_code'=>$project_info['manager_team_code'],
				'manager_id'=>$project_info['manager_id'],
				'date_skincare'=>date('Y-m-d')
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>$this->common_lib->get_cfg(array('doctor','skincare','treat_region','manager_team')),
			'rs'=>$skin_info
		);
		$this->_render('info_widget_skin_input', $datum, 'inc');
	}

	/**
	 * 환자정보 > 수술간호일지
	 * @return [type] [description]
	 */
	public function info_widget_nurse() {
		$p = $this->param;
		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];

		//검색조건설정
		$where = array('patient_no'=>$patient_no); //, 'project_no'=>$project_no

		$manager = $this->common_lib->get_user();
		$rs = $this->Patient_Model->select_widget_paging('patient_nurse', $where, $offset, $limit, 'date_nurse DESC' );

		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['doctor_name'] = $manager[$row['doctor_id']];
				$row['assistance_name'] = $manager[$row['assistance_id']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$datum = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete')
			)
		);

		$this->_render('info_widget_nurse', $datum, 'inc');
	}

	/**
	 * 환자정보 > 수술간호일지 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_nurse_input() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$nurse_no = $this->param['no'];

		$doctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));//의사
		$treat_region = $this->common_lib->get_code_item( '01' ); //진료부위
		$treat_type = $this->common_lib->get_code_item( '09' ); //치료구분
		$nurse = $this->_get_nurse();

		if($nurse_no > 0) {
			$mode = 'update';
			$nurse_info = $this->Patient_Model->select_widget_row('patient_nurse', array('no'=>$nurse_no));
			$nurse_info['treat_info'] = $this->patient_lib->set_treat($nurse_info['treat_info']);

			$nurse_info['nurse_id_arr']=explode(',',$nurse_info['nurse_id']);
			$nurse_info['medication']=unserialize($nurse_info['medication']);
		}
		else {
			$mode = 'insert';
			$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project');
			$nurse_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'doctor_id'=>$project_info['doctor_id'],
				'treat_type_code'=>key($treat_type),
				'date_nurse'=>date('Y-m-d')
			);
		}

		//pre($nurse_info);

		$datum = array(
			'mode'=>$mode,
			'cfg'=>$this->common_lib->get_cfg(array('doctor','treat_region','nurse','treat_type')),
			'rs'=>$nurse_info
		);

		$this->_render('info_widget_nurse_input', $datum, 'inc');
	}

	/**
	 * 환자정보 > Dr's order
	 * @return [type] [description]
	 */
	public function info_widget_doctor() {
		$p = $this->param;

		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;

		//검색조건설정
		$where = array('patient_no'=>$this->patient_no, 'project_no'=>$this->project_no);

		$manager = $this->common_lib->get_user();
		$rs = $this->Patient_Model->select_widget_paging('patient_doctor', $where, $offset, $limit, 'date_doctor DESC' );

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['doctor_name'] = $manager[$row['doctor_id']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$datum = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete')
			)
		);

		$this->_render('info_widget_doctor', $datum, 'inc');
	}

	/**
	 * 환자정보 > Dr's order 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_doctor_input() {
		$p = $this->param;

		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];
		$doctor_no = $p['no'];

		if($doctor_no > 0) {
			$mode = 'update';
			$doctor_info = $this->Patient_Model->select_widget_row('patient_doctor', array('no'=>$doctor_no));
			$doctor_info['treat_info'] = $this->patient_lib->set_treat($doctor_info['treat_info']);
		}
		else {
			$mode = 'insert';
			$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project');
			$doctor_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'doctor_id'=>$project_info['doctor_id'],
				'manager_team_code'=>$project_info['manager_team_code'],
				'manager_id'=>$project_info['manager_id'],
				'date_doctor'=>date('Y-m-d')
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=> $this->common_lib->get_cfg(array('manager_team','doctor','nurse','treat_region')),
			'rs'=>$doctor_info
		);

		$this->_render('info_widget_doctor_input', $datum, 'inc');
	}

	/**
	 * 환자정보 > 수납내역
	 * @return [type] [description]
	 */
	public function info_widget_pay($patient_no) {
		$p = $this->param;
		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;
		$patient_no = ($patient_no)?$patient_no:$p['patient_no'];
		$project_no = $p['project_no'];
		$search = $p['search'];
		if(empty($search)) {
			$search = array(
				'pay_type' => 'all'
			);
		}

		//검색조건설정
		$where = array('patient_no'=>$patient_no, 'project_no'=>$project_no, 'is_delete'=>'N');
		foreach($search as $k=>$v) {
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				default :
					$where[$k] = $v;
					break;
			}
		}


		$manager = $this->common_lib->get_user();
		$rs = $this->Patient_Model->select_widget_paging('patient_pay', $where, $offset, $limit, 'no DESC' );

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['doctor_name'] = $manager[$row['doctor_id']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['idx'] = $idx;
				if(CASHX) $row['comment'] = '';
				$list[] = $row;
				$idx--;
			}
		}
		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project', '*, (type+0) AS type_idx');

		$datum = array(
			'tab'=>'pay',
			'count'=>$rs['count'],
			'project'=>$project_info,
			'list'=>$list,
			'paging'=>$paging,
			'search'=>$search,
			'cfg'=>array(
				'search'=>array(
					'all'=>'전체',
					'paid'=>'입금',
					'refund'=>'환불'
				)
			),
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete_pay')
			)
		);

		$this->_render('info_widget_pay', $datum, 'inc');
	}

	/**
	 * 환자정보 > 수납내역 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_pay_input() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$pay_no = $this->param['no'];

		$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project', '*, (type+0) AS type_idx');

		if($pay_no > 0) {
			$mode = 'update';
			$pay_info = $this->Patient_Model->select_widget_row('patient_pay', array('no'=>$pay_no));
			if(CASHX) $pay_info['comment'] = '';
			$diff = time()-strtotime($pay_info['date_insert']);
			
			$auth_save = (date('Y-m-d')==substr($pay_info['date_insert'],0,10))?true:false;
			//$auth_save  = ($diff<86400*1 || DEV === true)?true:false;

		}
		else {
			$mode = 'insert';
			$auth_save = true;

			$pay_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'manager_team_code'=>$project_info['manager_team_code'],
				'manager_id'=>$project_info['manager_id'],
				'doctor_id'=>$project_info['doctor_id'],
				'calc_type'=>'basic',
				'pay_type'=>'paid',
				'sales_type'=>'수술',
				'patient_no'=>$patient_no,
				'date_paid'=>date('Y-m-d'),
				'receipt_type'=>'미발행'
			);
		}

		$datum = array(
			'mode'=>$mode,
			'auth'=>array(
				'save'=>$auth_save
			),
			'cfg'=>$this->common_lib->get_cfg(array('manager_team','doctor','treat_region','coordi','bank','card')),
			'project'=>$project_info,
			'rs'=>$pay_info
		);
		// pre($datum);

		$this->_render('info_widget_pay_input', $datum, 'inc');
	}

	/**
	 * 환자정보 > 챠트목록
	 * @desc 상담차트인경우 프로젝트 번호 조건 제외
	 * @return [type] [description]
	 */
	public function info_widget_chart($kind) {

		//검색조건설정
		$search = $this->param['search'];
		$where = array();
		$where['patient_no'] = $this->patient_no;
		$where['is_delete']='N';
		if($kind != 'cst') $where['project_no'] = $this->project_no;

		if(empty($search)) {
			$search = array(
				'subject' => 'all',
				'kind'=>$kind
			);
		}

		foreach($search as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;
			switch($k) {
				default :
					$where[$k] = $v;
					break;
			}
		}

		$chart_list = $this->Patient_Model->select_patient_all($where, 'patient_chart', '*, (subject+0) AS subject_idx');

		$cfg_search = $this->_get_chart_kind($kind);

		foreach($chart_list as $row){
			if(!file_exists($_SERVER['DOCUMENT_ROOT'].$row['file_path']) || !file_exists($_SERVER['DOCUMENT_ROOT'].$row['file_path_thumbnail'])) continue;
			if($kind == 'checkup') $charts[$row['times']][] = $row;
			else $charts[] = $row;
		}
		$datum = array(
			'basic'=>array(
				'no'=>$this->patient_no
			),
			'kind'=>$kind,
			'search'=>$search,
			'cfg'=>array(
				'search' =>	$cfg_search
			),
			'charts'=>$charts,
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete_chart')
			)
		);
		$this->_render('info_widget_chart', $datum, 'inc');
	}

	private function _get_chart_kind($kind) {
		if($kind == 'normal') {
			$cfg_search = array(
				'all'=>'전체',
				'성형외과'=>'성형외과',
				'피부과'=>'피부과',
				'산부인과'=>'산부인과'
			);
		}
		else if($kind == 'cst') {
			$cfg_search = array(
				'all'=>'전체',
				'초진기록지'=>'초진기록지',
				'고객상담시트'=>'고객상담시트',
				'기타'=>'기타'
			);
		}
		else {
			$cfg_search = array(
				'all'=>'전체',
				'X-RAY'=>'X-RAY',
				'CT'=>'CT',
				'초음파'=>'초음파',
				'혈액검사'=>'혈액검사',
				'재료사진'=>'재료사진',

			);
		}

		return $cfg_search;

	}

	/**
	 * 환자정보 > 일반챠트 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_chart_input() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));

		$kind = $this->param['kind'];
		$cfg_search = $this->_get_chart_kind($kind);
		array_shift($cfg_search);

		$datum = array(
			'kind'=>$kind,
			'cfg'=>array(
				'team'=>$team,
				'treat_region'=>$treat_region,
				'search'=>$cfg_search
			),
			'rs' => array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'date_chart'=>date('Y-m-d')
			)
		);
		$this->_render('info_widget_chart_input', $datum ,'inc');
	}

	/**
	 * 환자정보 > 전후사진비교
	 * @return [type] [description]
	 */
	public function info_widget_photo($kind) {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$photo_list = $this->Patient_Model->select_patient_all(array('patient_no'=>$patient_no, 'kind'=>$kind), 'patient_photo', '*, (times+0) AS times_idx', 'date_photo ASC, no ASC');

		$photos = array();
		foreach($photo_list as $row){
			if(!file_exists($_SERVER['DOCUMENT_ROOT'].$row['file_path']) || !file_exists($_SERVER['DOCUMENT_ROOT'].$row['file_path_thumbnail'])) continue;
			$photos[$row['times_idx']][$row['date_photo']][] = $row;

		}

		// pre($photos);
		$datum = array(
			'photos'=>$photos,
			'kind'=>$kind,
			'title'=>($kind=='normal')?'전후사진':'상담사진',
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete_photo')
			)
		);

		$this->_render('info_widget_photo', $datum, 'inc');
	}



	/**
	 * 환자정보 > 전후사진비교 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_photo_input() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));
		$kind = $this->param['kind'];
		$datum = array(
			'kind'=>$kind,
			'rs'=>array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'date_photo'=>date('Y-m-d')
			)
		);

		$this->_render('info_widget_photo_input', $datum, 'inc');
	}

	/**
	 * 환자정보 > 컴플레인일지
	 * @return [type] [description]
	 */
	public function info_widget_complain() {
		$p = $this->param;
		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;
		$patient_no = $p['patient_no'];
		$search = $p['search'];


		if(empty($search)) {
			$search = array(
				'method' => 'all'
			);
		}

		//검색조건설정
		$where = array('patient_no'=>$patient_no, 'is_delete'=>'N');
		foreach($search as $k=>$v) {
			if($v == 'all' || (!$v && $v!=='0')) continue;
			switch($k) {
				default :
					$where[$k] = $v;
					break;
			}
		}

		$manager = $this->common_lib->get_user();
		$rs = $this->Patient_Model->select_widget_paging('patient_complain', $where, $offset, $limit, 'date_complain DESC, no DESC' );

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				$userinfo = $this->common_lib->get_user_info($row['writer_id']);

				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['writer'] = $userinfo;
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$datum = array(
			'tab'=>'complain',
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'search'=>$search,
			'cfg'=>array(
				'search'=>array(

				)
			),
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete')
			)
		);

		$this->_render('info_widget_complain', $datum, 'inc');

	}

	/**
	 * 환자정보 > 컴플레인일지등록
	 * @return [type] [description]
	 */
	public function info_widget_complain_input() {
		$p = $this->param;
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];
		$consulting_no = $p['no'];

		if($consulting_no > 0) {
			$mode = 'update';
			$consulting_info = $this->Patient_Model->select_widget_row('patient_complain', array('no'=>$consulting_no), '*');
			$consulting_info['treat_info'] = $this->patient_lib->set_treat($consulting_info['treat_info']);
		}
		else {
			$mode = 'insert';
			$project_info = $this->Patient_Model->select_patient_row(array('no'=>$this->param['project_no']), 'patient_project');
			$consulting_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no,
				'manager_team_code'=>$project_info['manager_team_code'],
				'manager_id'=>$project_info['manager_id'],
				'date_complain'=>date('Y-m-d'),
				'method_code'=>'1'
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>$this->common_lib->get_cfg(array('manager_team','treat_region')),
			'rs'=>$consulting_info
		);
		// pre($datum);
		$this->_render('info_widget_complain_input', $datum, 'inc');

	}

	public function info_widget_photo_compare() {
		$datum = array(
			'photo'=>$this->param['photo']
		);
		$this->_render('info_widget_photo_compare', $datum, 'inc');
	}


	/**
	 * 환자정보 > 동의서
	 * @return [type] [description]
	 */
	public function info_widget_agree() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];

		$search = $this->param['search'];

		if(empty($search)) {
			$search = array(
				'category' => 'all'
			);
		}

		//검색조건설정
		$where = array('patient_no'=>$patient_no);
		if($project_no) {
			//$where['project_no'] = $project_no;
		}
		foreach($search as $k=>$v) {
			if($v == 'all' || (!$v && $v!=='0')) continue;
			switch($k) {
				default :
					$where[$k] = $v;
					break;
			}
		}

		$agree_list = $this->Patient_Model->select_patient_all($where, 'patient_agree', '*, (category+0) AS category_idx');


		$agree = array();
		foreach($agree_list as $row){
			if(!file_exists($_SERVER['DOCUMENT_ROOT'].$row['file_path']) || !file_exists($_SERVER['DOCUMENT_ROOT'].$row['file_path_thumbnail'])) continue;
			$agree[] = $row;

		}
		$datum = array(
			'agree'=>$agree,
			'cfg'=>array(
				'search'=>array(
					'all'=>'전체',
					'수술동의서'=>'수술동의서',
					'사진활용동의서'=>'사진활용동의서',
					'부위별수술동의서'=>'부위별수술동의서'
				)
			),
			'search'=>$search,
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete')
			)
		);
		$this->_render('info_widget_agree', $datum, 'inc');
	}

	/**
	 * 환자정보 > 동의서 등록/수정
	 * @return [type] [description]
	 */
	public function info_widget_agree_input() {

		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));
		$agree_no = $this->param['no'];
		if($agree_no) {
			$mode = 'update';
			$agree_info = $this->Patient_Model->select_widget_row('patient_agree', array('no'=>$agree_no));
		}
		else {
			$mode = 'insert';
			$agree_info = array(
				'patient_no'=>$patient_no,
				'project_no'=>$project_no
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>$this->common_lib->get_cfg(array('team','treat_region')),
			'rs'=>$agree_info
		);

		$this->_render('info_widget_agree_input', $datum, 'inc');
	}

	public function info_widget_material() {
		$this->load->model('Stock_Model');

		$p = $this->param;
		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit'];
		$offset = ($page-1)*$limit;
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];
		$search = $p['search'];


		//검색조건설정
		$where = array('patient_no'=>$patient_no);
		if($project_no>0) {
			$where['project_no'] = $project_no;
		}

		$StockClassify = $this->Stock_Model->select_category(array('is_use'=>'Y'));

		$rs = $this->Patient_Model->select_widget_paging('patient_material', $where, $offset, $limit, 'no DESC' );
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['goods_info'] = unserialize($row['goods_info']);
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}
		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		//합계
		$sum_field = "COUNT(distinct(goods_no)) AS kind_count, SUM(use_count*goods_price) AS price_total";
  		$sum = $this->Patient_Model->select_widget_row('patient_material',array('project_no'=>$project_no), $sum_field);
  		$sum['price_total'] = round($sum['price_total'],-1);

		$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project', '*, (type+0) AS type_idx');
		$datum = array(
			'tab'=>'material',
			'count'=>$rs['count'],
			'project'=>$project_info,
			'sum'=>$sum,
			'list'=>$list,
			'paging'=>$paging,
			'auth'=>array(
				'delete'=>$this->common_lib->check_auth_group('tab_delete'),
				'view_price'=> $this->common_lib->check_auth_group('chart_goods_price') //재료비 확인권한
			),
				'StockClassify'=>$StockClassify,
		);

		//return_json($datum);


		$this->_render('info_widget_material', $datum, 'inc');
	}

	public function info_widget_material_input() {
		$this->load->model('Stock_Model');

		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$material_no = $this->param['no'];

		$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project', '*, (type+0) AS type_idx');

		$category_subject = $this->Stock_Model->select_category(array('code_parent'=>'000S', 'is_use'=>'Y'));
		$anesthetize = $this->Stock_Model->select_category(array('code_parent'=>'000A', 'is_use'=>'Y'));
		$StockClassify = $this->Stock_Model->select_category(array('code_parent'=>'000D', 'is_use'=>'Y'));

		if($material_no > 0) {
			$mode = 'update';
			$material_info = $this->Patient_Model->select_widget_row('patient_material', array('no'=>$material_no));
			$material_info['goods_info'] = unserialize($material_info['goods_info']);
			list($int, $decimal) = explode('.', $material_info['use_count']);
			$material_info['use_count'] = array(
				'int'=>$int,
				'decimal'=>$decimal
			);
		}
		else {
			$mode = 'insert';

			$material_info = array(
				'group_code'=>'000H',
				'patient_no'=>$patient_no,
				'project_no'=>$project_no
			);
		}
		$datum = array(
			'mode'=>$mode,
			'tab'=>'material',
			'cfg'=>$this->common_lib->get_cfg(array('team','doctor')),
			'basic'=>array(
				'category_subject'=>$category_subject,
				'anesthetize'=>$anesthetize,
				'StockClassify'=>$StockClassify
			),
			'project'=>$project_info,
			'rs'=>$material_info
		);

		$this->_render('info_widget_material_input', $datum, 'inc');
	}

	public function consulting_list() {
		$search_word = $this->param['search_word'];
		$this->load->model('Consulting_model');

		$where['(name LIKE "%'.$search_word.'%" OR messenger LIKE "%'.$search_word.'%" OR tel LIKE "%'.$search_word.'%")'] = null;
		$where['use_flag'] = 'Y';
		$where['biz_id'] = $this->session->userdata('ss_biz_id');
		$where['hst_code'] = $this->session->userdata('ss_hst_code');

		if(!$this->common_lib->check_auth_group('info_patient')) {
			$where['team_code'] = $this->session->userdata('ss_team_code');
		}

		$rs = $this->Consulting_model->get_cst_all($where,'cst_seqno, name, sex, birth, tel, messenger, email, addr, path, job_code, team_code, charge_user_id, media');
		if($rs) {
			$result = array();
			foreach($rs as $row) {
				$row['search'] = implode(',', array_filter(array($row['tel'], $row['messenger'])));
				$result[] = $row;
			}
			return_json(true,'', $result);
		}
		else {
			return_json(false);
		}
	}

	public function consulting_info() {


	}

	function get_treat_item() {
		$this->load->model('Treat_Model');
		$parent_code = $this->param['region_code'];
		$treat_item = $this->common_lib->get_code_item( '02' , $parent_code, 'all'); //진료부위
		// pre($treat_item);
		$treat = array();
		$cost_config = $this->Treat_Model->select_cost();
		// pre($cost_config);
		// pre($treat_item);
		foreach($treat_item as $k=>$v) {
			$cost = $cost_config[$v['etc']];
			// pre($cost);
			$treat[$k] = array(
				'title'=>$v['title'],
				'cost'=>array(
					'no'=>$cost['no'],
					'origin'=>$cost['cost_origin'],
					're'=>$cost['cost_re'],
					'abroad_1'=>$cost['cost_abroad_1'],
					'abroad_2'=>$cost['cost_origin'],
					'service'=>0
				)
			);
		}
		if($treat) {
			return_json(true, '', $treat);
		}
		else {
			return_json(false);
		}
	}

	/**
	 * 환자 상담팀 변경
	 * @return [type] [description]
	 */
	function patient_manager() {
		$datum = array(
			'patient_no'=>$this->param['patient_no'],
			'cfg'=>array(
				'team'=>$this->common_lib->get_cfg('team')
			)
		);
		$this->_render('patient_manager', $datum, 'inc');
	}

	function search() {
		$datum = array();
		$this->_display('patient_search', $datum );
	}

	function get_count_tab() {
		$p = $this->param;
		$tab_arr = array('chart','pay','doctor','nurse','treat','skin','consulting','photo', 'material', 'agree','complain');
		$cnt = array();
		foreach($tab_arr as $tbl) {
			if($tbl == 'chart') {
				$cnt["{$tbl}_normal"] = $this->Patient_Model->count_patient(array('patient_no'=>$p['patient_no'], 'project_no'=>$p['project_no'], 'kind'=>'normal', 'is_delete'=>'N'), "patient_{$tbl}");
				$cnt["{$tbl}_checkup"] = $this->Patient_Model->count_patient(array('patient_no'=>$p['patient_no'], 'project_no'=>$p['project_no'], 'kind'=>'checkup', 'is_delete'=>'N'), "patient_{$tbl}");
				$cnt["{$tbl}_cst"] = $this->Patient_Model->count_patient(array('patient_no'=>$p['patient_no'], 'project_no'=>$p['project_no'], 'kind'=>'cst', 'is_delete'=>'N'), "patient_{$tbl}");
			}
			else if($tbl == 'photo') {
				$cnt["{$tbl}_normal"] = $this->Patient_Model->count_patient(array('patient_no'=>$p['patient_no'], 'kind'=>'normal'), "patient_{$tbl}");
				$cnt["{$tbl}_cst"] = $this->Patient_Model->count_patient(array('patient_no'=>$p['patient_no'], 'kind'=>'cst'), "patient_{$tbl}");
			}
			// 20170203 kruddo : 수술 등록하지 않아도 상담일지 작성을 할 수 있도록 수정 -> 수술등록 하지 않은 경우 project_no 가 없어서 count가 0이 됨.
			else if(in_array($tbl, array('consulting', 'complain','agree', 'nurse','treat'))){
				$cnt[$tbl] = $this->Patient_Model->count_patient(array('patient_no'=>$p['patient_no'], 'is_delete'=>'N'), "patient_{$tbl}");
			}
			else {
				$cnt[$tbl] = $this->Patient_Model->count_patient(array('patient_no'=>$p['patient_no'], 'project_no'=>$p['project_no'], 'is_delete'=>'N'), "patient_{$tbl}");

			}
		}
		return_json(true, '', $cnt);
	}

	function appointment() {
		$this->load->model('Manage_Model');
		$search = $this->session->userdata('search');
		$status = $this->Manage_Model->get_code_item('07', '', 'all',array());

		$datum = array(
			'auth'=>array(
				'patient_delete'=>$this->common_lib->check_auth_group('patient_delete'),
				'favorite_pt'=>$this->common_lib->check_auth_group('favorite_pt')
			),
			'status'=>$status,
			'cfg'=>$this->common_lib->get_cfg(array('team','appointment_status','appointment_type','date')),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:1,
		);

		$this->_render('appointment_lists', $datum );
	}

	function appointment_inner() {
		$page = $this->input->post('page');
		$limit = 15; //($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		$where_offset = array('p.is_delete'=>'N');

		//검색조건설정
		$where = array();
		parse_str($this->input->post('search'), $assoc);
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'appointment_date_s':
					$where['appointment_date >=']="{$v}";
				break;
				case 'appointment_date_e':
					$where['appointment_date <=']="{$v}";
				break;
				case 'manager_team_code':
				case 'manager_id':
					$where['pa.'.$k]="{$v}";
				break;
				case 'treat_cost_no':
					$treat_route = $this->patient_lib->treat_children($v);
					$where['pa.treat_info IN ('.$treat_route.')']=NULL;
				break;
				case 'word':
					$where["(p.name LIKE '%{$v}%' OR p.mobile LIKE '%{$v}%' OR p.chart_no LIKE '%{$v}%' )"]=NULL;
				break;
				case 'type_operate':
					$arr = explode('|',$v);
					$where["type_code IN('".implode("','",$arr)."')"] = NULL;
 				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		if(DEV === true) {
			// pre($where);
		}

		$this->load->Model('Manage_Model');
		$doctor = $this->common_lib->get_cfg('doctor');
		$manager = $this->common_lib->get_user();
		$status = $this->common_lib->get_cfg('appointment_status');

		$type = $this->common_lib->get_cfg('appointment_type');

		$rs = $this->Patient_Model->select_appointment_paging($where,$offset, $limit, $where_offset);
		//pre($where);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			//pre($rs['list']);
			foreach($rs['list'] as $row) {
				$row['status_text'] = $status[$row['status_code']];
				$row['type_text'] = $type[$row['type_code']];

				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['appointment_time'] = substr($row['appointment_time_start'],0,5).'~'.substr($row['appointment_time_end'],0,5);
				if($row['treat_info']) {
					$treat_info = $this->patient_lib->set_treat($row['treat_info'], 'array');
					$row['treat_info'] = implode('<BR />',$treat_info);
				}
				else $row['treat_info'] = '';

				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);

				$row['manager_name'] = $manager[$row['manager_id']];

				$appointment_mk = strtotime($row['appointment_date'].' '.$row['appointment_time_start']);
				$row['sms_date'] = date('m월 d일',$appointment_mk);
				$row['sms_time'] = date('H시 i분',$appointment_mk);
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();
		$paging['this'] = $page;
		//
		$datum = array(
			'count'=>$rs['count'],
			'auth'=>array(
				'appointment_delete'=>$this->common_lib->check_auth_group('appointment_delete')
			),
			'list'=>$list,
			'paging'=>$paging
		);


		if($rs['count']['search']>0) {
			return_json(true, '', $datum);
		}
		else {
			return_json(false, '', $datum);
		}
	}

	/**
	 * 수술예약목록
	 *
	 * @return void
	 */
	function appointment_operate() {
		$this->load->model('Manage_Model');
		$search = $this->session->userdata('search');
		$status = $this->Manage_Model->get_code_item('07', '', 'all',array());

		$cfg = $this->common_lib->get_cfg(array('team','appointment_status','appointment_type','date'));
		
		$cfg_doctor = array(
			'06-001'=>'박상훈(Dr.P)', //Dr.P
			'06-012'=>'정태광(Dr.T)', //Dr.T
			'06-016'=>'이현직(Dr.L)', //Dr.L
			'06-014'=>'황재홍(Dr.H)', //Dr.H 
			'06-013'=>'최원철(Dr.C)', //Dr.C
		);

		$cfg = array_merge($cfg, array('appointment_type_doctor'=>$cfg_doctor));
	
		$datum = array(
			'auth'=>array(
				'patient_delete'=>$this->common_lib->check_auth_group('patient_delete'),
				'favorite_pt'=>$this->common_lib->check_auth_group('favorite_pt')
			),
			'type_operate'=>array(
				'06-001'=>$cfg['appointment_type']['06-001'],
				'06-012'=>$cfg['appointment_type']['06-012'],
				'06-013'=>$cfg['appointment_type']['06-013'],
				'06-016'=>$cfg['appointment_type']['06-016'],
				'06-014'=>$cfg['appointment_type']['06-014'],
			),
			'status'=>$status,
			'cfg'=>$cfg,
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:1,
		);

		$this->_render('appointment_operate', $datum );
	}

	/**
	 * 수술예약목록
	 *
	 * @return void
	 */
	function appointment_operate_inner() {
		$page = $this->input->post('page');
		$limit = 15; //($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		$where_offset = array(
			"type_code IN('06-001','06-012','06-013','06-016','06-014')"=>NULL,
			"status_code NOT IN('07-012','07-028')"=>NULL,
			'p.is_delete'=>'N'
		);

		//검색조건설정
		$where = array(
			
		);
		parse_str($this->input->post('search'), $assoc);
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'appointment_date_s':
					$where['appointment_date >=']="{$v}";
				break;
				case 'appointment_date_e':
					$where['appointment_date <=']="{$v}";
				break;
				case 'manager_team_code':
				case 'manager_id':
					$where['pa.'.$k]="{$v}";
				break;
				case 'treat_cost_no':
					$treat_route = $this->patient_lib->treat_children($v);
					$where['pa.treat_info IN ('.$treat_route.')']=NULL;
				break;
				case 'word':
					$where["(p.name LIKE '%{$v}%' OR p.mobile LIKE '%{$v}%' OR p.chart_no LIKE '%{$v}%' )"]=NULL;
				break;
				case 'type_operate':
					$arr = explode('|',$v);
					$where["type_code IN('".implode("','",$arr)."')"] = NULL;
				break;
				case 'doctor_id':
					// $where['p.doctor_id']="{$v}";
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		if(DEV === true) {
			// pre($where);
		}

		$this->load->Model('Manage_Model');
		$doctor = $this->common_lib->get_cfg('doctor');
		$manager = $this->common_lib->get_user();
		$status = $this->common_lib->get_cfg('appointment_status');

		$type = $this->common_lib->get_cfg('appointment_type');

		$rs = $this->Patient_Model->select_appointment_paging($where,$offset, $limit, $where_offset);
		//pre($where);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			//pre($rs['list']);
			foreach($rs['list'] as $row) {
				$row['status_text'] = $status[$row['status_code']];
				$row['type_text'] = $type[$row['type_code']];

				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['appointment_time'] = substr($row['appointment_time_start'],0,5).'~'.substr($row['appointment_time_end'],0,5);
				if($row['treat_info']) {
					$treat_info = $this->patient_lib->set_treat($row['treat_info'], 'array');
					$row['treat_info'] = implode('<BR />',$treat_info);
				}
				else $row['treat_info'] = '';

				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);

				$row['manager_name'] = $manager[$row['manager_id']];

				$appointment_mk = strtotime($row['appointment_date'].' '.$row['appointment_time_start']);
				$row['sms_date'] = date('m월 d일',$appointment_mk);
				$row['sms_time'] = date('H시 i분',$appointment_mk);
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();
		$paging['this'] = $page;
		//
		$datum = array(
			'count'=>$rs['count'],
			'auth'=>array(
				'appointment_delete'=>$this->common_lib->check_auth_group('appointment_delete')
			),
			'list'=>$list,
			'paging'=>$paging
		);


		if($rs['count']['search']>0) {
			return_json(true, '', $datum);
		}
		else {
			return_json(false, '', $datum);
		}
	}

	private function _get_nurse() {
		return $this->common_lib->search_user(array('occupy_code'=>'03-002'), 'user_id, name'); //간호직군
	}




	/**
	 * 뷰
	 * @param  string $tmpl 템플릿경로
	 * @param  array $datum 데이터셋
	 * @return void
	 */
	private function _display($tmpl, $datum) {
		$this->load->view('hospital/patient/'.$tmpl, $datum );
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "hospital/patient/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}


