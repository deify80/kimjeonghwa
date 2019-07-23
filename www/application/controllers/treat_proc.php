<?php
/**
 * 진료관리 Process
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Treat_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->load->model(array('Patient_Model', 'Treat_Model'));

		// $this->yield = TRUE;
	}

	//예약 코드설정(유형/상태)
	function save_settings() {
		$group_code = $this->param['group_code'];
		$biz_id = $this->common_lib->get_biz_group(true); //$this->session->userdata('ss_biz_id');
		$hst_code = $this->session->userdata('ss_hst_code');
		$sort_list= $this->param['sort'];
		// pre($sort_list);exit;

		$this->load->model('Manage_Model');
		$this->Manage_Model->update_code_item(array("use_flag"=>'N'), array('group_code'=>$group_code, 'biz_id'=>$biz_id));

		//pre($sort_list);exit;
		if(is_array($sort_list)) {
			$success = true;
			foreach($sort_list as $idx => $sort) {

				if($sort['code'] == 'new') {
					$count = $this->Manage_Model->count_code(array('group_code'=>$group_code, 'hst_code'=>$hst_code));
					$code = $group_code.'-'.str_pad(($count+1),3,'0',STR_PAD_LEFT);
				}
				else {
					$code = $sort['code'];
				}

				$record = array(
					'code'=>$code,
					'title'=>$sort['name'],
					'group_code'=>$group_code,
					'order_no'=>$idx+1,
					'hst_code'=>$hst_code,
					'biz_id'=>$biz_id,
					'use_flag'=>'Y',
					'etc'=>(is_array($sort['etc']))?serialize($sort['etc']):$sort['etc']
				);

				//pre($record);


				$rs = $this->Manage_Model->duplicate_code_item($record);
				if(!$rs) $success = false;
			}
		}
		else {
			$success = true;
		}

		if($success) {
			return_json(true);
		}
		else {
			return_json(false, '잠시 후에 다시 시도해 주세요.');
		}

	}

	function save_settings_holiday() {
		$p = $this->param;
		$year = $p['year'];
		$month = $p['month'];
		$biz_id = $this->common_lib->get_biz_group(true);
		//월 초기화
		$this->Treat_Model->update(array('is_holiday'=>'N'), array('DATE_FORMAT(date, "%Y%m") = "'.$year.$month.'"'=>null, 'biz_id'=>$biz_id), 'treat_holiday');
		// echo $this->db->last_query();
		$success = true;
		foreach($p['holiday'] as $h) {
			$record = array(
				'date'=> "{$year}-{$month}-{$h}",
				'is_holiday'=>'Y',
				'biz_id'=>$biz_id,
				'date_insert'=>NOW
			);

			$rs = $this->Treat_Model->insert_holiday($record);
			if(!$rs) $success = false;
		}

		if($success) {
			return_json(true, '저장되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 예약정보 저장
	 * 회원정보에서 수정하는 예약기록은 환자정보를 수정하지 않음(referer==patient)
	 * @return [type] [description]
	 */
	function save_appointment() {
		$p = $this->param;

		$patient_no = $p['patient_no'];

		// 20170320 kruddo : 수납 내역 미등록시 수술 등록 하지 못하게 수정 - 삭제 권한이 없는 경우만, 박주희 팀장 제외
		//
		/* 20170809 해당기능 삭제요청 by 김아름실장
		if(!$this->common_lib->check_auth_group('appointment_delete') && $this->session->userdata('ss_user_id')!='jhpark'){
			if($p['appointment']['visit'] != "3" && $p['appointment']['status'] == "07-025"){
				$query = $this->db->query("select no from patient_project where patient_no=".$patient_no." and date_project='".$p['appointment']['date']."' and (paid_total-amount_refund) > 0");
				$count = $query->num_rows();

				if($count < 1){
					return_json(false,'수납 내역이 없어 등록 할 수 없습니다.');
				}
			}
		}
		*/
		// 20170320 kruddo : 수납 내역 미등록시 수술 등록 하지 못하게 수정


		if($p['referer']!='patient') {
			//회원정보

			$record = array(
				'name'=>$p['name'],
				'grade_type'=>$p['grade_type'],
				'doctor_id'=>$p['doctor_id'],
				'manager_team_code'=>$p['manager_team_code'],
				'manager_id'=>$p['manager_id'],
				'introducer_no'=>$p['introducer_id'],
				'job_code'=>$p['job_code'],
				'company'=>$p['company'],
				'path_code'=>$p['path_code'],
				'is_o'=>$p['is_o'],
				'sex'=>$p['sex'],
				'comment'=>$p['comment'],
				'agree_privacy'=>(isset($p['agree_privacy']))?$p['agree_privacy']:'N',
				'agree_sms'=>(isset($p['agree_sms']))?$p['agree_sms']:'N',
				'agree_email'=>(isset($p['agree_email']))?$p['agree_email']:'N',
				'stay_status'=>$p['stay_status'],
				'is_o'=>(!empty($p['is_o']))?$p['is_o']:'N',
				'is_x'=>(!empty($p['is_x']))?$p['is_x']:'N'
			);


			if(isset($p['birth'])) $record['birth'] = $p['birth'];
			if(isset($p['sex'])) $record['birth'] = $p['birth'];

			//전화번호 권한이 있는 경우에만 수정가능
			if($this->common_lib->check_auth_group('ex_phone')) {
				$record['mobile'] = $p['mobile'];
			}

			$record['jumin'] = $p['jumin'];
			$record['messenger'] = $p['messenger'];
			$record['tel'] = $p['tel'];
			$record['email'] = $p['email'];
			$record['zipcode'] = $p['zipcode'];
			$record['address'] = $p['address'];
			$record['address_detail'] = $p['address_detail'];


			if($patient_no > 0) {
				$mode = 'update';
				$rs = $this->Patient_Model->update_patient($record, array('no'=>$patient_no));
			}
			else {
				$mode = 'insert';
				$record['cst_seqno'] = $p['cst_seqno'];
				$record['biz_id'] = $this->session->userdata('ss_biz_id');
				$record['hst_code'] = $this->session->userdata('ss_hst_code');

				//차트번호
				$count = $this->Patient_Model->count_patient(array('DATE_FORMAT(date_insert,"%Y-%m-%d")'=>date('Y-m-d')));
				$chart_no = date('Ymd').'-'.str_pad(($count+1),3,'0',STR_PAD_LEFT);

				$record['media'] = $p['media']; //미디어코드

				$record['chart_no'] = $chart_no;
				$record['date_insert'] = NOW;
				$rs = $this->Patient_Model->insert_patient($record);
			}

			if($rs) {
				$this->load->model('Consulting_Model');
				if($mode=='insert') {
					$patient_no = $rs;
					@mkdir($this->dir.$patient_no);
					if($p['cst_seqno'] > 0) {
						$this->Consulting_Model->update_consulting(array('patient_no'=>$patient_no), array('cst_seqno'=>$p['cst_seqno']));
					}
				}

				//상담정보 동기화
				if($p['cst_seqno'] > 0) {

					$cst_record_old = $this->Consulting_Model->select_consulting_row(array('cst_seqno'=>$p['cst_seqno'])); //기존데이터
					$cst_record = array(
						'name'=>$record['name'],
						'messenger'=>$record['manager_id'],
						'tel'=>str_replace('-','',$record['mobile']),
						'birth'=>str_replace('-','',$record['birth']),
						'sex'=>$record['sex'],
						'email'=>$record['email'],
						'job_code'=>$record['job_code']
					);

					if(!$p['auth_update']){
						//unset($record['messenger']);
						unset($cst_record['tel']);
						//unset($record['email']);
					}


					$rs_sync = $this->Consulting_Model->update_consulting($cst_record, array('cst_seqno'=>$p['cst_seqno']));
					if($rs_sync) {
						$this->load->library('consulting_lib');
						$this->consulting_lib->save_log($cst_record_old, $cst_record);
					}
				}
			}
			else {
				return_json(false,'환자정보 저장에 실패하였습니다.');
			}
		}


		//예약정보
		$appointment_no = $p['appointment_no'];
		$p = $p['appointment'];

		$room_no = (in_array($p['status'], array('07-016','07-023')))?$p['room_no']:0;

		$record = array(
			'appointment_date'=>$p['date'],
			'appointment_time_start'=>$p['time_start'],
			'appointment_time_end'=>$p['time_end'],
			'type_code'=>$p['type'],
			'status_code'=>$p['status'],
			'room_no'=>$room_no,
			'visit'=>$p['visit'],
			'doctor_id'=>$p['doctor_id'],
			'acceptor_id'=>$p['acceptor_id'],
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id'],
			'skincare_id'=>$p['skincare_id'],
			'treat_info'=>$p['treat'],
			'comment'=>$p['comment']
		);
		if($appointment_no > 0) {
			$rs_appointment = $this->Patient_Model->update_patient($record, array('no'=>$appointment_no), 'patient_appointment');
		}
		else {
			$mode = 'insert';
			$record['patient_no']=$patient_no;
			$record['date_insert'] = NOW;
			$rs_appointment = $this->Patient_Model->insert_patient($record, 'patient_appointment');
		}

		if($rs_appointment) {

			$this->_sync_appointment($patient_no);


			return_json(true,'예약정보가 저장되었습니다.', array('date'=>$record['date']));
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}

	function modify_appointment() {
		$p = $this->param;

		$time_start = strtotime($p['min_time'])+($p['col_start']*1800);
		$time_end = $time_start+($p['col_span']*1800);

		$record = array(
			'type_code'=>$p['type_code'],
			'appointment_time_start'=>date('H:i:s',$time_start),
			'appointment_time_end'=>date('H:i:s',$time_end)
		);

		$rs = $this->Patient_Model->update_patient($record, array('no'=>$p['appointment_no']), 'patient_appointment');
		if($rs) {
			return_json(true);
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}

	/**
	 * 예약 복사
	 * @return [type] [description]
	 */
	function copy_appointment() {
		$from_no = $this->param['from'];
		$from_rs = $this->Patient_Model->select_appointment_row(array('pa.no'=>$from_no), "pa.*, TIME_TO_SEC(TIMEDIFF(pa.appointment_time_end, pa.appointment_time_start)+0)/60 as timediff");
		$to = $this->param['to'];

		$time_start = $to['time_start'];
		$time_end = date('H:i:s',strtotime($time_start)+($from_rs['timediff']*60));
		if($time_end > APPOINTMENT_END) {
			$time_end = APPOINTMENT_END; //마감시각보다 큰경우 마감시각으로 조정
		}

		$record = array(
			'patient_no'=>$from_rs['patient_no'],
			'appointment_date'=>$to['appointment_date'],
			'appointment_time_start'=>$time_start,
			'appointment_time_end'=>$time_end,
			'type_code'=>$to['type_code'],
			'status_code'=>$from_rs['status_code'],
			'visit'=>$from_rs['visit'],
			'doctor_id'=>$from_rs['doctor_id'],
			'acceptor_id'=>$from_rs['acceptor_id'],
			'manager_team_code'=>$from_rs['manager_team_code'],
			'manager_id'=>$from_rs['manager_id'],
			'skincare_id'=>$from_rs['skincare_id'],
			'treat_info'=>$from_rs['treat_info'],
			'comment'=>$from_rs['comment'],
			'date_insert'=>NOW
		);

		$rs = $this->Patient_Model->insert_patient($record, 'patient_appointment');
		if($rs) {
			return_json(true);
		}
		else {
			return_json(false, '잠시 후에 다시 시도해 주세요.');
		}
	}

	function status_appointment() {

		$status_code = $this->param['status_code'];
		$appointment_no = $this->param['appointment_no'];

		if(strpos($status_code, '#') !== false) {
			list($status_code, $room_no) = explode('#', $status_code);
		}

		$record = array(
			'status_code'=>$status_code
		);

		$room_status = array('07-016','07-023');
		if(in_array($status_code,$room_status) && $room_no) {
			$record['room_no'] = $room_no;
		}

		$rs = $this->Patient_Model->update_patient($record, array('no'=>$appointment_no), 'patient_appointment');
		if($rs) {
			return_json(true);
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}

	}

	function remove_appointment() {
		$patient_no = $this->param['patient_no'];
		$appointment_no = $this->param['appointment_no'];

		$rs = $this->Patient_Model->remove_patient(array('no'=>$appointment_no), 'patient_appointment');
		if($rs) {
			$this->_sync_appointment($patient_no);
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function search_appointment() {
		$query = $this->param['query'];

		$where["(p.name LIKE '%{$query}%') OR (p.mobile LIKE '%{$query}%') OR (p.chart_no LIKE '%{$query}%')"] = null;
		$rs = $this->Patient_Model->select_appointment($where);


		if($rs) {
			$doctor = $this->common_lib->get_cfg('doctor');
			$status = $this->common_lib->get_code_item('07', '');
			$this->load->library('patient_lib');
			$idx = count($rs);
			foreach($rs as $row) {
				$row['status_text'] = $status[$row['status_code']];
				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['appointment_time'] = substr($row['appointment_time_start'],0,5).'~'.substr($row['appointment_time_end'],0,5);
				$row['treat_info'] = $this->patient_lib->set_treat($row['treat_info'], 'text');
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
			return_json(true,'', $list);
		}
		else {
			return_json(false);
		}

	}

	function _sync_appointment($patient_no) {
		$appointment_last = $this->Patient_Model->select_appointment_last(array('patient_no'=>$patient_no));//최근예약일
		$appointment_count = $this->Patient_Model->select_appointment_count(array('patient_no'=>$patient_no)); //예약횟수
		$rs = $this->Patient_Model->update_patient(array('appointment_last'=>$appointment_last,'appointment_count'=>$appointment_count), array('no'=>$patient_no));
		// echo $this->db->last_query();
		return $rs;
	}

	/**
	 * 공지사항 저장
	 * @return [type] [description]
	 */
	function save_notice() {
		$p = $this->param;
		$record = array(
			'writer_id'=>$this->session->userdata('ss_user_id'),
			'contents'=>htmlspecialchars($p['contents'], ENT_QUOTES),
			'display_start'=>$p['display_start'],
			'display_end'=>$p['display_end'],
			'biz_id'=>$this->session->userdata('ss_biz_id'),
			'date_insert'=>NOW
		);
		$rs = $this->Treat_Model->insert($record, 'treat_notice');
		if($rs) {
			$record['no'] = $rs;
			return_json(true,'공지사항이 등록되었습니다.', $record);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 공지사항 삭제
	 * @return [type] [description]
	 */
	function remove_notice() {
		$no = $this->param['no'];
		$rs = $this->Treat_Model->delete(array('no'=>$no), 'treat_notice');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}

	}

	function save_cost() {
		$p = $this->param;


		if($p['mode'] == 'insert') {

			$parents = array_filter($p['parents']);
			$depth = count($parents)+1;
			$parent_no = ($depth>1)?array_pop($parents):'0';
			$max = $this->Treat_Model->select_max(array('parent_no'=>$parent_no), 'sort');
			foreach($p['cost'] as $k=>$v) {
				$cost[$k] = str_replace(',','',$v);
			}
			$record = array(
				'depth'=>$depth,
				'parent_no'=>$parent_no,
				//'route'=>implode('_',$p['parents']),
				'name'=>$p['name'],
				'cost_origin'=>$cost['origin'],
				'cost_re'=>$cost['re'],
				'cost_abroad_1'=>$cost['abroad_1'],
				'cost_abroad_2'=>$cost['abroad_2'],
				'sort'=>$max['sort']+1
			);
			// pre($record);

			$rs = $this->Treat_Model->insert($record,'treat_cost');

			//route
			if($rs) {
				$route = implode('_',$p['parents']).'_'.$rs;
				$where = array(
					'no'=>$rs
				);
				$this->Treat_Model->update(array('route'=>$route), $where, 'treat_cost');
			}

		}
		else {
			if($p['kind']) {
				$record = array(
					'cost_'.$p['kind']=>str_replace(',','',$p['cost'])
				);
			}
			else {

				foreach($p['cost'] as $k=>$v) {
					$cost[$k] = str_replace(',','',$v);
				}

				$record = array(
					'name'=>$p['name'],
					'cost_origin'=>$cost['origin'],
					'cost_re'=>$cost['re'],
					'cost_abroad_1'=>$cost['abroad_1'],
					'cost_abroad_2'=>$cost['abroad_2'],
				);
			}

			$where = array(
				'no'=>$p['no']
			);
			$rs = $this->Treat_Model->update($record, $where, 'treat_cost');
		}

		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function json_cost() {
		$parent_no = $this->param['parent_no'];
		$rs = $this->Treat_Model->select_cost(array('parent_no'=>$parent_no));
		if($rs) {
			return_json(true,'',$rs);
		}
		else {
			return_json(false);
		}
	}

	function match_cost() {
		$p = $this->param;
		$record = array(
			'etc'=>$p['cost_no']
		);
		$where = array(
			'code'=>$p['code']
		);

		$this->load->model('Manage_Model');
		$rs = $this->Manage_Model->update_code_item($record, $where);
		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function settings_cost() {
		$p = $this->param;
		$this->load->model('Common_Model');
		$record = array(
			'pack'=>$p['pack'],
			'field'=>'cost',
			'value'=>serialize($p['cost'])
		);
		$rs = $this->Common_Model->update_config($record);
		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 수가 삭제
	 * @return [type] [description]
	 */
	function remove_cost() {
		$no = $this->param['no'];
		$rs = $this->Treat_Model->delete(array('no'=>$no), 'treat_cost');
		if($rs) {
			//하위분류 삭제
			$this->Treat_Model->delete(array('parent_no'=>$no), 'treat_cost');
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 진료부위/항목명 등록/수정
	 * @return [type] [description]
	 */
	function save_treat() {
		$p = $this->param;
		$this->load->model('Manage_Model');
		if($p['mode'] == 'insert') {
			$max = $this->Treat_Model->select_max(array('group_code'=>$p['group_code']), 'code', 'code_item');
			list($gc,$c) = explode('-',$max['code']);

			$code = $p['group_code'].'-'.str_pad(($c+1),'3','0',STR_PAD_LEFT);
			$record = array(
				'code'=>$code,
				'title'=>$p['title'],
				'hst_code'=>$this->session->userdata('ss_hst_code'),
				'group_code'=>$p['group_code']
			);
			if($p['parent_code']) {
				$record['parent_code']=$p['parent_code'];
			}

			$rs = $this->Manage_Model->insert_code_item($record);
		}
		else {
			$record = array(
				'title'=>$p['title']
			);

			$rs = $this->Manage_Model->update_code_item($record, array('code'=>$p['code']));
		}

		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}

	}

	/**
	 * 진료부위/항목명 삭제
	 * @return [type] [description]
	 */
	function remove_treat() {
		$this->load->model('Manage_Model');
		$code = $this->param['code'];
		$rs = $this->Manage_Model->update_code_item(array('use_flag'=>'N'), array('code'=>$code));
		if($rs) {
			//하위분류 삭제
			$this->Manage_Model->update_code_item(array('use_flag'=>'N'), array('parent_code'=>$code));
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}
}
