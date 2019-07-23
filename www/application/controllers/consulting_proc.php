<?php
/**
 * 작성 : 2014.10.28
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Consulting_proc extends CI_Controller {



	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'Consulting_model'
		) );

		$this->load->library( array (
				'consulting_lib'
		) );

		$this->param = $this->input->post(NULL, true);
	}



	public function update() {
		//$this->output->enable_profiler( TRUE );

		$p = $this->param;
		$cst_seqno = $this->input->post( 'cst_seqno' );
		$row = $this->Consulting_model->get_cst_info( $cst_seqno );

		if ($row['cst_status'] != $this->input->post( 'cst_status' )) {
			$this->Consulting_model->cst_status_insert( $cst_seqno, $this->input->post( 'cst_status' ) );
		}

		$this->load->model('treat_model');

		$treat_cost_rs = $this->treat_model->select_cost_row(array('no'=>$p['treat_cost_no']));
		$treat_cost_route = $treat_cost_rs['route'];

		$input = null;
		$input['name'] = $this->input->post( 'name' );
		$input['sex'] = $this->input->post( 'sex' );
		$input['birth'] = $this->input->post( 'birth' );
		$input['email'] = set_null( $this->input->post( 'email' ) );
		$input['messenger'] = set_null( $this->input->post( 'messenger' ) );
		$input['call_time'] = $this->input->post( 'call_time' );
		$input['addr'] = $this->input->post( 'addr' );
		$input['character'] = $this->input->post( 'character' );
		$input['cost'] = $this->input->post( 'cost' );
		$input['other_status'] = $this->input->post( 'other_status' );
		$input['reoper_cnt'] = $this->input->post( 'reoper_cnt' );
		$input['oper_date'] = $this->input->post( 'oper_date' );
		$input['complain_status'] = $this->input->post( 'complain_status' );
		$input['sales'] =  str_replace(',','',$this->input->post( 'sales' ));
		$input['sales_origin'] =  str_replace(',','',$this->input->post( 'sales_origin' ));
		$input['job_code'] = $this->input->post( 'job_code' );
		$input['treat_cost_no'] = set_null( $p['treat_cost_no']);
		$input['treat_cost_route'] = set_null( $treat_cost_route);
		$input['treat_cost_no_interest'] = $p['treat_cost_no_interest'];
		$input['etc'] = $this->input->post( 'etc' );
		$input['mod_date'] = TIME_YMDHIS;
		$input['cst_status'] = $this->input->post( 'cst_status' );
		$input['appointment_date'] = $this->input->post( 'appointment_date' );
		$input['appointment_time'] = ($p['appointment_time'])?$p['appointment_time'].":00":'00:00:00';
		$input['plan_date'] = $this->input->post( 'plan_date' );
		$input['plan_time'] = ($p['plan_time'])?$p['plan_time'].":00":'00:00:00';
		$input['age'] = ($p['age'])?$p['age']:'0';
		$input['locale'] = $this->input->post( 'locale' );
		$input['refund_type'] = $this->input->post( 'refund_type' );
		$input['refund'] = str_replace(',','',$this->input->post( 'refund' ));
		$input['closing_user_id'] = $p['closing_user_id']; //클로징담당자 2017-05-11

		$input['cpa'] = $this->input->post('cpa');

		switch ($input['cst_status']) {
			case '50': //내원취소
				$input['cst_status_reason'] = $this->input->post( 'cst_status_reason' );
			break;
			case '99': //수술완료
				$input['surgery_date'] = $this->input->post( 'surgery_date' );
			break;
		}

		//전화번호 노출권한 있는경우만 변경가능처리
		$auth_ex_phone2 = $this->common_lib->check_auth_group('ex_phone2');
		if($auth_ex_phone2) {
			$input['tel'] = str_replace( '-', '', $this->input->post( 'tel' ) );
		}

		if ($this->input->post( 'charge_user_id' ) != "") $input['charge_user_id'] = $this->input->post( 'charge_user_id' );
		if ($this->input->post( 'team_code' ) != "") $input['team_code'] = $this->input->post( 'team_code' );

		$this->Consulting_model->update_cst( $this->input->post( 'cst_seqno' ), $input );

		// if($this->session->userdata( 'ss_dept_code' ) == '90')
		$value = $this->consulting_lib->check_charge( $row, $this->input->post( 'cst_status' ), $_POST );
		$this->consulting_lib->save_log($row, $input);
		$record = $input;
		$input = null;
		if ($value['charge_date'] != '') $input['charge_date'] = $value['charge_date'];
		if ($value['permanent_status'] != '') $input['permanent_status'] = $value['permanent_status'];
		$input['filter'] = ($value['filter'] > $this->consulting_lib->filter_limit) ? 'Y' : 'N';

		if ($row['charge_date'] < TIME_YMDHIS && $value['charge_date'] != '') $input['appointment_cnt'] = 0;


		$rs = $this->Consulting_model->update_cst( $cst_seqno, $input );
		if($rs) {
			$this->load->model('Patient_Model');
			//연동환자 데이터 동기화(이름,메신저,전화번호, 생년월일, 성별, 이메일, 직업)
			$patient_record = array(
				'name'=>$record['name'],
				'messenger'=>$record['messenger'],
				'mobile'=>$record['tel'],
				'birth'=>$record['birth'],
				'sex'=>$record['sex'],
				'email'=>$record['email'],
				'job_code'=>$record['job_code']
			);
			$this->Patient_Model->update_patient($patient_record, array('cst_seqno'=>$cst_seqno));



			// 20170327 kruddo : 팀 변경 시 담당팀장 같이 변경
			$this->load->model('User_model');

			$where['team_code']=$record['team_code'];
			$where['status']='1';
			$where['position_code']='52';

			$manage = $this->User_model->select_user_paging($where, $offset, $limit, $where_offset);
			foreach($manage['list'] as $row) {
				$manager_id = $row['user_id'];
			}

			$query = $this->db->query("select no from patient where cst_seqno=".$cst_seqno);
			$prs = $query->result_array();
			foreach($prs as $row){
				$patient_no = $row['no'];
			}

			$record_data = array(
				'manager_team_code'=>$record['team_code'],
				'manager_id'=>$manager_id,
			);

			$rs = $this->Patient_Model->update_patient($record_data, array('no'=>$patient_no));
			if($rs) {
				$tbl_arr = array('doctor','consulting','project','pay', 'appointment');
				foreach($tbl_arr as $tbl) {
					$this->Patient_Model->update_patient($record_data, array('patient_no'=>$patient_no), 'patient_'.$tbl);
				}
			}

			// 20170327 kruddo : 팀 변경 시 담당팀장 같이 변경
		}


		/* 내원예약횟수 제한 해제
		if ($this->input->post( 'cst_status' ) == '51' && $row['appointment_date'] != $this->input->post( 'appointment_date' )) {
			$this->Consulting_model->update_appointment_cnt($this->input->post( 'cst_seqno' ));

			$type = $this->consulting_lib->check_type($value['charge_date']);
			$select_date_valid = $this->consulting_lib->check_appointment_cnt($type, $row['appointment_cnt']+1);
		}*/



		if (set_null( $value['charge_date'] ) != null) {
			$this->Consulting_model->close_contact( $this->input->post( 'cst_seqno' ) );
		}

		$json = null;
		$json['select_date_valid'] = $select_date_valid;
		echo json_encode( $json );
	}

	public function update_chargedate() {
		$p = $this->param;
		$record = array(
			'charge_date'=>$p['charge_date']
		);
		$cst_seqno = $p['cst_seqno'];
		$rs = $this->Consulting_model->update_cst( $cst_seqno, $record);
		if($rs) {
			return_json(true,'유효기간이 변경되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	public function update_path() {
		$p = $this->param;
		$record = array(
			'path'=>$p['path']
		);
		$cst_seqno = $p['cst_seqno'];
		$rs = $this->Consulting_model->update_cst( $cst_seqno, $record);
		if($rs) {
			return_json(true,'유입경로가 변경되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	function memo_add() {

		$change_item = '상담내용|상담내용 추가';
		$this->save_log_change( $change_item );

		$input = null;
		$input['mod_date'] = TIME_YMDHIS;
		$this->Consulting_model->update_cst( $this->input->post( 'cst_seqno' ), $input );

		$input = null;
		$input['memo'] = $this->input->post( 'memo' );
		$input['cst_seqno'] = $this->input->post( 'cst_seqno' );
		$input['reg_date'] = TIME_YMDHIS;
		$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );

		$result = $this->Consulting_model->memo_insert( $input );
		if ($result) {
			$input['memo'] = nl2br( $input['memo'] );
			$input['reg_date'] = set_long_date_format( '-', TIME_YMDHIS );
			$input['name'] = $this->session->userdata( 'ss_name' );
			echo json_encode( $input );
		}
	}



	function save_log_change($change_item) {
		if ($this->input->post( 'change_item' ) != '') $change_item = $this->input->post( 'change_item' );
		$exp_change_item = explode( '㉿', $change_item );
		foreach ( $exp_change_item as $i => $val ) {

			if ($val != "") {
				$exp_value = explode( '|', $val );
				$list[$exp_value[0]] = $exp_value[1];
			}
		}

		$total = 0;
		foreach ( $list as $i => $val ) {
			$input = null;
			$input['title'] = strip_tags( $i );
			$input['contents'] = strip_tags( $val );
			$input['reg_date'] = TIME_YMDHIS;
			$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
			$input['cst_seqno'] = $this->input->post( 'cst_seqno' );
			$input['team_code'] = $this->session->userdata( 'ss_team_code' );
			$this->Consulting_model->log_insert( $input );
			$total ++;
		}

		return $total;
	}

	public function do_close() {
		$result = $this->Consulting_model->close_contact( $this->input->post( 'cst_seqno' ), $this->input->post( 'seqno' ) );
		echo $result;
	}

	/**
	 * MYDB설정
	 * @return [type] [description]
	 */
	public function do_permanent() {

		$permanent_max = PERMANENT_MAX;
		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$hour = date('H');
		$minute = date('i');

		$check_list = $this->input->post('chk');

		foreach ( $check_list as $i => $val ) {
			$cst_seqno[] = $val;
		}

		$imp_cst_seqno = implode( ',', $cst_seqno );

		$where = null;
		$where[] = "team_code='" . $this->session->userdata( 'ss_team_code' ) . "'";
		$where[] = 'cst_seqno in (' . $imp_cst_seqno . ')';
		$where[] = "permanent_status='N'";
		$where[] = "use_flag='Y'";

		$result = $this->Consulting_model->get_cst_in( $where );
		$date = null;
		$is_valid = true;
		foreach ( $result as $i => $row ) {
			$key = substr( $row['reg_date'], 0, 6 );
			$date_total[$key] ++;
			$date[$key] = $key;

			$is_valid = ($row['cst_status'] == '99' || $date_total[$key] > $permanent_max) ? false : true;
			if ($is_valid === false) break;
		}

		$imp_date = implode( ',', $date );

		$where = null;
		$where[] = 'SUBSTRING(reg_date, 1, 6) in (' . $imp_date . ')';
		if (is_array( $date )) $permanent_result = $this->Consulting_model->get_permanent_cst( $where );
		foreach ( $permanent_result as $i => $row ) {
			$key = $row['date'];
			$is_valid = ((intval( $row['total'] ) + $date_total[$key]) > $permanent_max) ? false : true;
			if ($is_valid === false) break;
		}

		if ($is_valid === false) {
			$msg = 'MY DB 설정 기준에 적합하지 않습니다.<br>-1개월당 '.PERMANENT_MAX.'개 초과<br>-수술완료건 제외';

		} else {


			foreach ( $check_list as $i => $cst_seqno ) {

				$input = null;
				$input['permanent_status'] = 'Y';
				$input['charge_date'] = date( "YmdHis", strtotime('+1 year', mktime( $hour, $minute, 0, $month, $day, $year )) );

				$this->Consulting_model->update_cst( $cst_seqno, $input );

			}

			$where = null;
			$where[] = "permanent_status='Y'";
			$permanent_total = $this->Consulting_model->get_cst_total($where);

			$msg = 'MY DB 설정 완료했습니다.';
		}

		return_json($is_valid, $msg, array('permanent_total'=>number_format($permanent_total)));
		// $json = null;
		// $json['msg'] = $msg;
		// $json['permanent_total'] = number_format($permanent_total);
		// echo json_encode( $json );
	}

	/**
	 * 재분배
	 * @return [type] [description]
	 */
	function simulator_assign() {
		$p = $this->input->post(NULL, true);
		$success = $fauilre = 0;
		$query = array();
		foreach($p['cst_seqno'] as $code=>$v) {
			$seqno_arr = explode(',',$v);
			foreach($seqno_arr as $seqno) {
				$sql = "UPDATE consulting_info SET team_code='".$p['to_team'][$code]."' WHERE cst_seqno='{$seqno}'";
				$query[] = $sql;
				if($p['act'] == 'exec') {
					$rs = $this->Consulting_model->update_consulting(array('team_code'=>$p['to_team'][$code]), array('cst_seqno'=>$seqno));
					if($this->db->affected_rows())	$success++;
					else $failure++;
				}
			}
		}

		$query_list = implode("<br />", $query);
		if($p['act'] == 'query') {
			$result = $query_list;
		}
		else {
			$result = "변경건수 : ".number_format($success)."건 <br />실패 : ".number_format($failure)."건<hr />";//$query_list;

		}
		echo $result;
	}

	function transfer_biz() {
		$p = $this->param;
		$where = array('cst_seqno'=>$p['cst_seqno']);
		$rs = $this->Consulting_model->select_consulting($where);
		$success = $failure = 0;
		foreach($rs as $row) {
			$this->db->trans_begin();
			$record = array(
				'biz_id'=>$p['biz_id'],
				'hst_code'=>$row['hst_code'],
				'media'=>'md21',
				'path'=>'D', //DB이관
				'name'=>$row['name'],
				'birth'=>$row['birth'],
				'sex'=>$row['sex'],
				'tel'=>$row['tel'],
				'messenger'=>$row['messenger'],
				'email'=>$row['email'],
				'category'=>$row['db_category'],
				'call_time'=>$row['call_time'],
				'type'=>'W',
				'reg_date'=>date('YmdHis'),
				'reg_user_id'=>$this->session->userdata('ss_user_id')
			);

			$rs_insert = $this->Consulting_model->db_insert($record);
			if($rs_insert) {
				$record_update = array(
					'cst_status'=>'09'
				);
				$where = array(
					'cst_seqno'=>$row['cst_seqno']
				);
				$this->Consulting_model->consulting_update($record_update, $where);
			}
			else {
				$failure++;
			}

			if ($this->db->trans_status() === FALSE) {
				$this->db->trans_rollback();
			} else {
				$this->db->trans_commit();
				$success++;
			}
		}

		return_json(true, '', array('success'=>$success, 'failure'=>$failure));
	}

	function change_charger() {
		$p = $this->param;
		$ss = $this->session->userdata;
		$charger = $this->adodb->getAssoc('SELECT user_id, name FROM user_info WHERE status=1');

		//consulting_info 팀변경
		$where = array('cst_seqno'=>$p['cst_seqno']);
		$record = array('charge_user_id'=>$p['user_id']);
		$result = $this->Consulting_model->consulting_update($record, $where);

		$cst_info = $this->Consulting_model->select_consulting_row($where);

		//변경로그
		$record_log = array(
			'title'=>'내원담당자',
			'contents'=>$charger[$p['user_id']],
			'reg_date'=>date('YmdHis'),
			'reg_user_id'=>$ss['ss_user_id'],
			'cst_seqno'=>$cst_info['cst_seqno'],
			'team_code'=>$ss['ss_team_code']
		);

		$this->Consulting_model->log_insert($record_log);

		if($result) {
			return_json(true, '변경되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}

	function change_closing() {
		$p = $this->param;
		$ss = $this->session->userdata;
		$charger = $this->adodb->getAssoc('SELECT user_id, name FROM user_info WHERE status=1');

		//consulting_info 팀변경
		$where = array('cst_seqno'=>$p['cst_seqno']);
		$record = array('closing_user_id'=>$p['user_id']);
		$result = $this->Consulting_model->consulting_update($record, $where);

		$cst_info = $this->Consulting_model->select_consulting_row($where);

		//변경로그
		$record_log = array(
			'title'=>'클로징실장',
			'contents'=>$charger[$p['user_id']],
			'reg_date'=>date('YmdHis'),
			'reg_user_id'=>$ss['ss_user_id'],
			'cst_seqno'=>$cst_info['cst_seqno'],
			'team_code'=>$ss['ss_team_code']
		);

		$this->Consulting_model->log_insert($record_log);

		if($result) {
			return_json(true, '변경되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}

	/**
	 * 즐겨찾기
	 * @return [type] [description]
	 */
	function save_favorite() {
		$cst_seqno = $this->param['cst_seqno'];

		$cst_info = $this->Consulting_model->select_consulting_row(array('cst_seqno'=>$cst_seqno));
		$favorite_arr = explode(',',$cst_info['favorite_user']);
		$user_no = $this->session->userdata('ss_user_no');

		if($this->param['favorite'] == 'Y') {
			array_push($favorite_arr, $user_no);
		}
		else {
			$key = array_search($user_no, $favorite_arr);
			unset($favorite_arr[$key]);
		}

		$favorite_arr = array_unique(array_filter($favorite_arr));
		$record = array(
			'favorite_user'=>implode(',',$favorite_arr)
		);
		$where = array('cst_seqno'=>$cst_seqno);

		$rs = $this->Consulting_model->consulting_update($record, $where);
		if($rs) {
			return_json(true,'변경되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}
}
