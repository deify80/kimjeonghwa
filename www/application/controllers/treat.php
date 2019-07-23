<?php
/**
 * 진료관리
 * 작성 : 2015.05.20
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Treat extends CI_Controller {

	private $time_start = APPOINTMENT_START;
	private $time_end = APPOINTMENT_END;

	public function __construct() {
		parent::__construct();
		session_start();
		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;

		$this->load->model(array (
			'Manage_Model',
			'Treat_Model',
			'Patient_Model'
		));
	}

	public function schedule() {

		$today = time();
		$team = $this->common_lib->get_team( '90' ); //상담팀
		$datum = array(
			'cfg'=>array(
				'today'=> date('Y-m-d'),
				'team'=>$team
			),
			'auth'=>array(
				'schedule_admin'=>$this->common_lib->check_auth_group('schedule_admin')
			)
		);
		$this->_render('schedule', $datum );
	}

	public function schedule_list() {
		$biz_group = $this->common_lib->get_biz_group();
		$type_cfg = $this->Manage_Model->get_code_item('06','','',array('biz_id'=>$biz_group));
		switch ($this->session->userdata("ss_team_code")) {
			// case '61': //피부팀
			// 	$type_keys = array('06-042','06-043');
			// break;
			case '74': //회복
			case '73': //치료
				$type_keys = array('06-029','06-031','06-032','06-033','06-035','06-036','06-037','06-038');
				break;
			case '72': //수술
				$type_keys = array('06-029','06-031','06-032','06-033','06-035','06-036','06-037','06-038','06-040');
				break;
			default:
				$type_keys = array_keys($type_cfg);
				break;
		}

		$type = array();
		foreach($type_keys as $key) {
			$type[$key]=$type_cfg[$key];
		}

		$status = $this->Manage_Model->get_code_item('07', '', 'all',array('biz_id'=>$biz_group) );

		$today = date('Y-m-d');
		$search_date = ($this->param['date'])?$this->param['date']:$today;
		$search_time = strtotime($search_date);


		$holiday = $this->Treat_Model->select(array('is_holiday'=>'Y', "DATE_FORMAT(date, '%Y%m')='".date('Ym',$search_time)."'"=>null, 'biz_id'=>$biz_group), 'date', 'treat_holiday', 'date');

		$standard_date = strtotime(date('Y-m-01', $search_time));

		$datum = array(
			'cfg'=>array(
				'holiday'=>$holiday,
				'days'=> array(
					'start'=>strtotime(date('Y-m-01', $search_time)),
					'end'=>strtotime(date('Y-m-t', $search_time)),
					'day'=>$this->config->item('yoil')
				),
				'today'=>$today,
				'search_date'=>$search_date,
				'time'=>array(
					'start'=>strtotime($search_date.' '.$this->time_start),
					'end'=>strtotime($search_date.' '.$this->time_end)
				),
				'month'=>array(
					'prev'=>strtotime('-1 month', $standard_date),
					'next'=>strtotime('+1 month', $standard_date)
				),
				'doctor'=>$this->common_lib->get_cfg('doctor'),
				'type'=>$type,
				'status'=>$status
			),
			'auth'=>array(
				'appointment_delete'=>$this->common_lib->check_auth_group('appointment_delete'),
				'ex_crmbutton1'=>$this->common_lib->check_auth_group('ex_crmbutton1')
			)
		);

		$this->_render('schedule_list', $datum , 'inc');
	}


	function schedule_appointment() {
		$this->load->library('patient_lib');
		$search = $this->param['search'];
		$date = $search['appointment_date'];

		$where = array();
		$where['biz_id'] = $this->common_lib->get_biz_group();

		foreach($search as $k=>$v) {
			if(empty($v)) continue;
			if($k == 'word') {

			}
			else {
				$where['pa.'.$k] = $v;
			}
		}
		$rs = $this->Patient_Model->select_appointment($where);
		if(DEV === true) {
			//echo $this->db->last_query();
		}

		$manager = $this->common_lib->get_user();
		$path = $this->config->item('all_path'); //유입경로

		//등급설정
		$grade_cfg = $this->Patient_Model->select_patient_all(array('is_use'=>'Y'),'patient_grade','no, code, rgb, name','','no');
		$team_idx = array(
			'90'=>'1', //1팀
			'91'=>'2', //2팀
			'92'=>'3', //3팀
			'93'=>'5', //5팀
			'94'=>'4', //4팀
			'95'=>'6', //6팀
			'96'=>'9', //9팀
			'98'=>'7' //7팀
		);
		$appointment = array();

		$room_status = array('07-016','07-023');
		foreach($rs as $row) {

			//(초진상담(06-040) or 재진상담(06-041)) & 상담팀인경우 소속팀예약기록만 노출 2015-09-12 by 이혜진
			if(in_array($row['type_code'], array('06-040','06-041'))) {
				if($this->session->userdata('ss_dept_code') == '90') {
					if($row['manager_team_code'] != $this->session->userdata('ss_team_code')) {
						continue;
					}
				}
			}

			$treat_info = $this->patient_lib->set_treat($row['treat_info'], 'array','long');

			$manager_team_code = $row['manager_team_code']; //$this->Patient_Model->select_patient_row(array('no'=>$row['patient_no']), 'patient', 'manager_team_code');
			$time_end = strtotime($row['appointment_date'].' '.$row['appointment_time_end']);
			$auth_manage = ($this->common_lib->check_auth_group('info_patient') || $manager_team_code == $this->session->userdata('ss_team_code'))?true:false;
			$grade = ($grade_cfg[$row['grade_no']]['code']=='N')?'':$grade_cfg[$row['grade_no']]['code'];
			$grade .= ($row['grade_type']!='N')?'-'.$row['grade_type']:'';
			$team = $this->User_model->get_team_list();

			$name = $row['name'].$team_idx[$row['manager_team_code']];

			if($row['is_o'] != 'N') {
				$name='<b>'.$row['is_o'].'</b> '.$name;
			}

			if(in_array($row['status_code'],$room_status) && $row['room_no']) {
				$name.='#'.$row['room_no'];
			}

			if($this->session->userdata('ss_team_code') == '98' && $row['path_code'] == 'E')  {
				$mobile = format_mobile($row['mobile']);
			}
			else {
				$mobile = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);
			}


			$appointment[] = array(
				'patient_no'=>$row['patient_no'],
				'sex'=>$row['sex'],
				'no'=>$row['no'],
				'name'=>$name,
				'grade'=>$grade,
				'treat'=>(is_array($treat_info))?implode(',',$treat_info):'',
				'row'=>$row['type_code'],
				'status_code'=>$row['status_code'],
				'start'=>$row['appointment_date'].' '.$row['appointment_time_start'],
				'end'=>(date('Y-m-d H:i:00', strtotime('-30 minutes',$time_end))),
				'mobile'=>$row['mobile'],
				'messenger'=>$row['messenger'],
				'visit_code'=>$row['visit_code'],
				'manager_team_code'=>$row['manager_team_code'],
				'auth'=>array(
					'manage'=>$auth_manage
				),
				'doctor_id'=>$row['doctor_id'],
				'popover'=>array(
					'patient_no'=>$row['patient_no'],
					'chart_no'=>$row['chart_no'],
					'title'=>$row['appointment_date'].' - '.substr($row['appointment_time_start'],0,5).'~'.substr($row['appointment_time_end'],0,5),
					'name'=>$row['name'],
					'sex'=>$row['sex'],
					'mobile'=>$mobile,
					'messenger'=>$row['messenger'],
					'grade'=>$grade_cfg[$row['grade_no']],
					'grade_type'=>$row['grade_type'],
					'birth'=>$row['birth'],
					'path'=>$path[$row['path_code']],
					'patient_comment'=>nl2br($row['patient_comment']),
					'doctor_name'=>$manager[$row['doctor_id']],
					'manager_team_name'=>$team[$row['manager_team_code']],
					'manager_name'=>$manager[$row['manager_id']],
					'acceptor_name'=>$manager[$row['acceptor_id']],
					'treat_info'=>implode('<br />',array_filter($treat_info)),
					'comment'=>nl2br($row['comment'])
				)
			);
		}

		if(DEV === true) {
			//pre($appointment);
		}

		if(date('i')>30) {
			$hour = date('H:30:00');
		}
		else {
			$hour = date('H:00:00');
		}

		if(empty($appointment)) {
			return_json(false, '', array('date'=>$date, 'hour'=>$hour));
		}
		else {
			return_json(true,'', array('date'=>$date, 'appointment'=>$appointment, 'hour'=>$hour));
		}
	}

	public function settings_holiday() {
		$datum = array(
			'cfg'=>array(
				'year'=>range(date('Y')-1, date('Y')+2),
				'month'=>range(1,12)
			)
		);
		$this->_render('settings_holiday', $datum, 'inc');
	}

	function settings_holiday_list() {
		// $p = $this->param;
		$year = $this->param['year'];
		$month = $this->param['month'];
		$biz_group = $this->common_lib->get_biz_group();
		$holiday = $this->Treat_Model->select(array('is_holiday'=>'Y', "DATE_FORMAT(date, '%Y%m')='{$year}{$month}'"=>null, 'biz_id'=>$biz_group), 'date', 'treat_holiday', 'date');


		$start_mk = strtotime($year.'-'.$month.'-01');
		$end_mk = strtotime($year.'-'.$month.'-'.date('t',$start_mk));

		$first_day = date('w',$start_mk);//첫요일
		$day_list = ($first_day>0)?range(1,$first_day):array();
		for($mk = $start_mk;$mk<=$end_mk;$mk+=86400) {
			$day_list[] = array(
				'd'=>date('d', $mk),
				'is_holiday'=>(array_key_exists(date('Y-m-d', $mk),$holiday))?'Y':'N'
			);
		}

		$last_day = date('w',$end_mk); //마지막날짜의 요일
		if($last_date<6) {
			$day_list = array_merge($day_list, range(1,(6-$last_day)));
		}

		// echo date('w',$end_mk);
		$day_list = array_merge($day_list, range(1,(6-date('w',$end_mk))));

		return_json(true,'',$day_list);
	}

	/**
	 * 예약유형설정
	 * @return [type] [description]
	 */
	public function settings_type() {
		$biz_id = $this->common_lib->get_biz_group();
		$types = $this->Manage_Model->get_code_item('06','','',array('biz_id'=>$biz_id));
		$datum = array(
			'types'=>$types
		);
		$this->_display('settings_type', $datum );
	}

	/**
	 * 예약상태설정
	 * @return [type] [description]
	 */
	public function settings_status() {
		$biz_id = $this->common_lib->get_biz_group();
		$status = $this->Manage_Model->get_code_item('07', '', 'all', array('biz_id'=>$biz_id));
		$datum = array(
			'status'=>$status
		);
		$this->_display('settings_status', $datum );
	}

	/**
	 * 즉시예약
	 * @return [type] [description]
	 */
	public function appointment_direct() {
		$p = $this->param;

		if($p['patient_no']>0) {
			$mode = 'insert';
		}
		else {
			$mode = 'update';
		}

		$auth_manage = ($this->common_lib->check_auth_group('info_patient') || $rs['manager_team_code']==$this->session->userdata('ss_team_code'))?true:false;


		$form_patient = $this->layout_lib->fetch_('/hospital/patient/patient_form.html', $patient_datum);

		$rs = array();
		$appointment_datum = array(
			'mode'=>'insert',
			'cfg'=>array(
				'doctor'=>$doctor,
				'job'=>$job,
				'team'=>$team,
				'acceptor'=>$user_coordi,
				'skincare'=>$user_skincare,
				'type'=>$type,
				'status'=>$status,
				'treat_region'=>$treat_region,
				'time'=>array(
					'start'=>strtotime($date.' '.$this->time_start),
					'end'=>strtotime($date.' '.$this->time_end)
				),
				'room'=>$room[$rs['status_code']]
			),
			'auth'=>array(
				'manage'=> $auth_manage,
				'appointment_type_dr'=>$this->common_lib->check_auth_group('appointment_type_dr')
			),
			'rs'=>$rs
		);

		$inc_appointment =  $this->layout_lib->fetch_('/hospital/treat/appointment_form.html', $appointment_datum);





		$datum = array(
			'mode'=>$mode,
			'inc'=>array(
				'patient'=>$form_patient,
				'appointment'=>$inc_appointment
			),
			'auth'=>array(
				'manage'=> $auth_manage
			)
		);
		$this->_render('appointment_direct', $datum , 'inc');
	}
	/**
	 * 진료예약
	 * @return [type] [description]
	 */
	public function appointment_input() {

		$p = $this->param;
		$this->load->library('Patient_lib');
		$date = ($p['date'])?$p['date']:date('Y-m-d');

		$job = $this->common_lib->get_code_item( '05' ); //직업
		$doctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));//의사
		$path = $this->config->item('all_path'); //유입경로
		$team = $this->common_lib->get_cfg('manager_team'); //상담팀
		$user_coordi = $this->common_lib->get_cfg('accept'); //코디팀직원
		$user_skincare = $this->common_lib->get_user( '61' ); //피부팀직원

		$type_origin = $this->Manage_Model->get_code_item('06'); //예약유형
		$status = $this->common_lib->get_cfg('appointment_status'); //예약상태


		$appointment_type_dr = $this->common_lib->check_auth_group('appointment_type_dr');
		$type_dr = array('06-001','06-002','06-003','06-012', '06-014'); //Dr.P, Dr.J, Dr.S, Dr.T, Dr.H
		$type = $type_origin;
		if(!$appointment_type_dr) {
			foreach($type_dr as $v) {
				unset($type[$v]); //Dr.P
			}
		}
		$appointment_no = $p['no'];
		if($appointment_no) {
			$mode = 'update';

			$rs = $this->Patient_Model->select_appointment_row(array('pa.no'=>$appointment_no)); //예약정보
			$patient = $this->Patient_Model->select_patient_row(array('no'=>$rs['patient_no']));//환자정보
			$patient = $this->patient_lib->set_patient($patient);
			//진료부위


			//pre($patient);
			$rs['treat_info'] = $this->patient_lib->set_treat($rs['treat_info']);
			$patient['mobile'] = $this->common_lib->manufacture_mobile($rs['mobile'], $rs['manager_team_code']);

			$auth_manage = ($this->common_lib->check_auth_group('info_patient') || $rs['manager_team_code']==$this->session->userdata('ss_team_code'))?true:false;

			//수술접근
			if(!$appointment_type_dr && in_array($rs['type_code'], $type_dr)) $auth_manage = false;

			$rs['type_code_txt'] = $type_origin[$rs['type_code']];

		}
		else {
			$mode = 'insert';
			$time_start = strtotime($date.' '.$p['time']);
			$auth_manage = true;
			$rs = array(
				'appointment_date'=>$date,
				'appointment_time_start'=>date('H:i:s',$time_start),
				'appointment_time_end'=>date('H:i:s',$time_start+1800),
				'type_code'=>$p['type'],
				'visit_code'=>'2',
				'status_code'=>'07-004'
			);


			if($p['patient_no']>0) {
				$patient = $this->Patient_Model->select_patient_row(array('no'=>$p['patient_no']));//환자정보
				$patient = $this->patient_lib->set_patient($patient);
			}
			else {
				if($p['cst_seqno']){
					$this->load->model('consulting_model');
					$cst = $this->consulting_model->select_consulting_row(array('cst_seqno'=>$p['cst_seqno']));
					//pre($cst);
					$patient = array(
						'cst_seqno'=>$cst['cst_seqno'],
						'name'=>$cst['name'],
						'sex'=>$cst['sex'],
						'mobile'=>$cst['tel'],
						'messenger'=>$cst['messenger'],
						'birth'=>$cst['birth'],
						'email'=>$cst['email'],
						'address'=>$cst['addr'],
						'media'=>$cst['media'],
						'grade_type'=>'N'

					);
				}
				else {
					$patient = array(
						'grade_type'=>'N',
						'sex'=>'F'
					);
				}

			}

		}


		$patient_datum = array(
			'mode'=>$mode,
			'referer'=>$p['referer'],
			'cfg'=>array(
				'doctor'=>$doctor,
				'job'=>$job,
				'manager_team'=>$team,
				'path'=>$path
			),
			'patient'=>$patient,
			'auth'=>array(
				'manage'=> $auth_manage,
				'ex_phone'=>$this->common_lib->check_auth_group('ex_phone')
			)
		);

		$patient_datum['auth']['update'] = ($rs['manager_team_code']==$this->session->userdata('ss_team_code') || $patient_datum['auth']['ex_phone'] || !$patient_datum['patient']['no'])?true:false; //개인정보 업데이트 권한
		$form_patient = $this->layout_lib->fetch_('/hospital/patient/patient_form.html', $patient_datum);

		$room = $this->common_lib->get_cfg('appointment_room'); //회복실/수술실


		$type_auth = array();
		foreach($type as $k=>$v) {
			if (!$appointment_type_dr && strpos($v,'Dr')!==false) continue;
			$type_auth[$k] = $v;
		}

		$appointment_datum = array(
			'cfg'=>array(
				'doctor'=>$doctor,
				'job'=>$job,
				'team'=>$team,
				'acceptor'=>$user_coordi,
				'skincare'=>$user_skincare,
				'type'=>$type,
				'status'=>$status,
				'treat_region'=>$treat_region,
				'time'=>array(
					'start'=>strtotime($date.' '.$this->time_start),
					'end'=>strtotime($date.' '.$this->time_end)
				),
				'room'=>$room[$rs['status_code']]
			),
			'auth'=>array(
				'manage'=> $auth_manage,
				'appointment_type_dr'=>$this->common_lib->check_auth_group('appointment_type_dr')
			),
			'rs'=>$rs
		);



		$inc_appointment =  $this->layout_lib->fetch_('/hospital/treat/appointment_form.html', $appointment_datum);

		$auth = $this->common_lib->check_auth_group('info_patient');
		$datum = array(
			'mode'=>$mode,
			'inc'=>array(
				'patient'=>$form_patient,
				'appointment'=>$inc_appointment
			),
			'auth'=>array(
				'manage'=> $auth_manage
			)
		);

		$this->_render('appointment_input', $datum , 'inc');
	}

	function appointment_search() {
		$datum = array();
		$this->_render('appointment_search', $datum , 'inc');
	}

	function appointment_popover(){
		$this->load->library('Patient_lib');

		$appointment_no = $this->param['appointment_no'];

		$appointment = $this->Patient_Model->select_appointment_row(array('pa.no'=>$appointment_no)); //예약정보
		$patient = $this->Patient_Model->select_patient_row(array('no'=>$appointment['patient_no']));//환자정보
		$patient_info = $this->patient_lib->set_patient($patient);

		$path = $this->config->item('all_path'); //유입경로
		$doctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));//의사
		$manager = $this->common_lib->get_user();

		//진료부위
		$appointment['treat_info'] = $this->patient_lib->set_treat($appointment['treat_info']);
		$appointment['doctor_name'] = $doctor[$appointment['doctor_id']];
		$appointment['manager_name'] = $manager[$appointment['manager_id']];
		$appointment['acceptor_name'] = $manager[$appointment['acceptor_id']];

		//등급설정
		$grade_cfg = $this->Patient_Model->select_patient_all(array('is_use'=>'Y'),'patient_grade','no, code, rgb, name','','no');
		$patient_info['grade'] = $grade_cfg[$patient_info['grade_no']];

		//유입경로
		$patient_info['path'] = $path[$patient_info['path']];

		$datum = array(
			'patient'=>$patient_info,
			'rs'=>$appointment
		);

		$content = $this->layout_lib->fetch_('/hospital/treat/appointment_popover.html', $datum);

		$data = array(
			'title'=>$appointment['appointment_date'].' - '.substr($appointment['appointment_time_start'],0,5).'~'.substr($appointment['appointment_time_end'],0,5),
			'content'=>$content
		);

		// return $data;
		return_json(true, '', $data);
	}

	public function patient() {
		$data = array();
		$this->_display('patient', $datum );
	}

	/**
	 * 공지사항
	 * @return [type] [description]
	 */
	public function notice_inline() {
		$today = date('Y-m-d');
		$biz_id = $this->common_lib->get_biz_group();
		$notice_list = $this->Treat_Model->select(array('display_start <='=>$today, 'display_end >=' =>$today, 'biz_id'=>$biz_id), '*', 'treat_notice');
		$datum = array(
			'notice'=>$notice_list
		);

		$this->_display('notice_inline', $datum );
	}

	/**
	 * 공지사항 등록
	 * @return [type] [description]
	 */
	public function notice_input() {
		$today = date('Y-m-d');
		$biz_id = $this->common_lib->get_biz_group();
		$notice_list = $this->Treat_Model->select(array('display_start <='=>$today, 'display_end >=' =>$today, 'biz_id'=>$biz_id),'*', 'treat_notice');
		$datum = array(
			'me'=>array(
				'user_id'=>$this->session->userdata("ss_user_id")
			),
			'notice'=>$notice_list
		);

		$this->_render('notice_input', $datum,'inc');
	}

	/**
	 * 수가표관리
	 * @return [type] [description]
	 */
	public function cost_tab() {
		$datum = array();
		$this->_render('cost_tab', $datum);
	}

	/**
	 * 수가표
	 * @return [type] [description]
	 */
	public function cost_table() {
		$rs =  $this->Treat_Model->select_cost('','','depth DESC, sort ASC');
		$items = $this->common_lib->get_code(array('group_code'=>'02', 'etc > '=>'0'),'etc','title');

		$list = array();
		$child = array();
		foreach($rs as $row) {
			$row['count'] = 1;
			$row['cost'] = unserialize($row['cost']);
			$row['match'] = (array_key_exists($row['no'], $items))?true:false;
			$parent_no = $row['parent_no'];
			$no = $row['no'];
			if($row['depth']==1) {
				if($child[$no]) {
					$list[$no] = $child[$no];
				}
				else {
					$list[$no] = $row;
				}
			}
			else {
				if (!isset($child[$parent_no])) {
					$child[$parent_no] = $rs[$parent_no];
				}

				$child[$parent_no]['children'][$no] = $row;

				if (isset($child[$no])) {
					$child[$parent_no]['children'][$no] = $child[$no];
					$child[$parent_no]['count'] += $child[$no]['count'];
				}
				else {
					$child[$parent_no]['count']++;
				}
			}
		}

		$list = array_sort($list, 'sort');
		$datum = array(
			'cfg'=>array(
				'cost'=>array('origin','re','abroad_1','abroad_2')
			),
			'list'=>$list
		);
		$this->_render('cost_table', $datum, 'inc');
	}

	/**
	 * 수가등록/수정
	 * @return [type] [description]
	 */
	public function cost_input() {
		$p = $this->param;
		$route = array();

		if($p['mode'] == 'insert') {
			$parent = $p['parent'];
			if($parent == 0) {
				$depth = 1;
				$direct = false;
				$categories = $this->Treat_Model->select_cost(array('depth'=>1));
			}
			else {
				$this->_cost_route($parent, $route);
				$direct = true;
				$depth = count($route)+1;
			}

			$cost = array(
				'route'=>$route,
				'depth'=>$depth
			);
		}
		else {
			$direct = true;
			$cost = $this->Treat_Model->select_cost_row(array('no'=>$p['no']));
			$this->_cost_route($cost['parent_no'], $route);
			// array_pop($route);
			$cost['route'] = $route;
		}

		$settings = $this->common_lib->get_config('treat','cost');
		$datum = array(
			'cfg'=>array(
				'direct'=>$direct,
				'categories'=>$categories,
				'settings'=>$settings
			),
			'mode'=>$p['mode'],
			'cost'=>$cost
		);

		$this->_render('cost_input', $datum, 'inc');
	}

	public function cost_common() {
		$datum = array();
		$this->_render('cost_common', $datum, 'inc');
	}

	/**
	 * 수가표 트리
	 * @return [type] [description]
	 */
	public function cost_tree() {
		$tree = array();
		$tree['id']='0';

		$rs =  $this->Treat_Model->select_cost('','','depth DESC, sort ASC');

		$list = array();
		$child = array();
		foreach($rs as $row) {

			if($row['depth']==3) {
				$userdata = array(
					array('name'=>'depth', 'content'=>$row['depth'])
				);

				$text = '<i class="fa fa-1 fa-stethoscope f-theme"></i> '.$row['name'].' ('.number_format($row['cost_origin']).'원)';
				$item = array('id'=>$row['no'], 'text'=>$text, 'im0'=>'blank.gif', 'imwidth'=>'0', 'userdata'=>$userdata);

			}
			else {
				$userdata = array(
					array('name'=>'depth', 'content'=>$row['depth'])
				);

				$item = array('id'=>$row['no'], 'text'=> $row['name'], 'userdata'=>$userdata);
			}

			$parent_no = $row['parent_no'];
			$no = $row['no'];
			if($row['depth']==1) {
				if($child[$no]) {
					$list[] = $child[$no];
				}
				else {
					$list[] = $item;
				}
			}
			else {
				if (!isset($child[$parent_no])) {
					$child[$parent_no] = array('id'=>$rs[$parent_no]['no'], 'text'=>$rs[$parent_no]['name']);
				}

				if (isset($child[$no])) {
					$child[$parent_no]['item'][] = $child[$no];
				}
				else {
					$child[$parent_no]['item'][] = $item;
				}
			}
		}
		$tree['item'] = $list;
		// $list = array_sort($list, 'sort');
		echo json_encode($tree);
	}

	private function _cost_route($no, &$route, $categories='') {

		if($no<1) {
			ksort($route);
			return true;
		}
		else {
			if(empty($categories)) {
				$categories = $this->Treat_Model->select_cost();
			}

			$row = $categories[$no];
			// pre($parent);
			$route[$row['depth']] = array(
				'no'=>$row['no'],
				'name'=>$row['name']
			);

			$this->_cost_route($row['parent_no'], $route);
		}
	}

	/**
	 * 진료항목 수가표매칭
	 * @return [type] [description]
	 */
	public function cost_match() {
		$list = array();
		$categories = $this->Treat_Model->select_cost();
		// pre($categories);
		$treat_region = $this->common_lib->get_code_item( '01' ); //진료부위
		// pre($categories);
		foreach($treat_region as $key=>$value) {
			$items = $this->common_lib->get_code_item( '02' , $key, 'all');
			foreach($items as $k=>$item) {
				if(!$item['etc']) continue;
				$category = $categories[$item['etc']];
				$route = array(
					'name'=>$category['name']
				);

				// echo pre($category);;
				// $this->_cost_route($category['no'], $route, $categories);
				$cost = array(
					'origin'=>$category['cost_origin'],
					're'=>$category['cost_re'],
					'abroad_1'=>$category['cost_abroad_1'],
					'abroad_2'=>$category['cost_abroad_2'],
				);

				$items[$k]['cost_info'] = array('route'=>$route, 'name'=>$category['name'], 'cost'=>$cost);
			}
			$list[$key] = array(
				'name'=>$value,
				'children'=>$items,
				'count'=>count($items)
			);
		}

		$categories = $this->Treat_Model->select_cost();

		$datum = array(
			'cfg'=>array(
				'categories'=>$categories
			),
			'list'=>$list
		);
		$this->_render('cost_match', $datum, 'inc');
	}

	/**
	 * 진료항목 등록/수정
	 * @return [type] [description]
	 */
	public function cost_match_input() {
		$p = $this->param;

		$treat_region = $this->common_lib->get_code_item( '01' ); //진료부위
		if($p['mode'] == 'insert') {
			$mode = 'insert';
			$parent_code = $p['parent_code'];
			if($parent_code) {
				$direct = true;
				$group_code = '02';
			}
			else {
				$direct = false;
				$group_code = '01';
			}

			$item = array(
				'group_code'=>$group_code,
				'parent_code'=>$parent_code
			);
		}
		else {
			$mode = 'update';
			$direct = true;
			$item =  $this->Manage_Model->select_code_row(array('code'=>$p['code'])); //진료부위
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'direct'=>$direct,
				'treat_region'=>$treat_region
			),
			'item'=>$item
		);

		// pre($datum);
		$this->_render('cost_match_input', $datum, 'inc');
	}

	/**
	 * 진료항목 트리
	 * @return [type] [description]
	 */
	public function cost_match_tree() {
		$datum = array(
			'code'=>$this->param['code']
		);
		$this->_render('cost_match_tree', $datum, 'inc');
	}

	/**
	 * 수가표 설정
	 * @return [type] [description]
	 */
	public function cost_settings() {
		$this->load->model('Common_Model');
		$settings = $this->Common_Model->select_config(array('pack'=>'treat','field'=>'cost'));
		// pre($settings);
		$datum = array(
			'settings'=>$settings
		);
		$this->_render('cost_settings',$datum, 'inc');
	}


	private function _display($tmpl, $datum) {
		$this->load->view('/hospital/treat/'.$tmpl, $datum );
	}

	private function _render($tmpl, $datum, $layout='default') {
		$this->yield = false;
		$tpl = "/hospital/treat/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
