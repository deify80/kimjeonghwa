<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Sms extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'Sms_model',
				'Consulting_model'
		) );
		$this->load->library( 'sms_lib' );
		$this->yield = TRUE;
		$this->layout = 'sms_layout';

		$this->max = 1500;			// 20170317 kruddo : SMS 제한 700건->1500건으로 늘림
		$this->sender = '15225515';
	}



	public function main() {

		$receiver_list = null;
		if (is_array( $this->input->post( 'chk' ) )) {
			foreach ( $this->input->post( 'chk' ) as $i => $val ) {
				$cst_seqno[] = $val;
			}
		}

		$imp_cst_seqno = implode( ',', $cst_seqno );
		$where = null;
		$where[] = 'cst_seqno in (' . $imp_cst_seqno . ')';
		if (is_array( $cst_seqno )) $result = $this->Consulting_model->get_cst_in( $where );
		foreach ( $result as $i => $row ) {
			if ($row['tel'] != '' && preg_match( '/[0-9]/', $row['tel'] )) $receiver_list[] = $row['tel'];
		}

		$sms_list = $this->input->post('sms', true);
		if(is_array($sms_list)) {
			foreach($sms_list as $mobile) {
				if ($mobile != '' && preg_match( '/[0-9]/', $mobile )) $receiver_list[] = $mobile;
			}
		}


		$category = array (
				'상담안내',
				'내원예약',
				'수술예약',
				'수술컨펌',
				'경과예약',
				'해피콜'
		);

		$data = array (
			'category'=>$category,
			'receiver_list'=>$receiver_list
		);
		$this->load->view( 'sms/input', $data );
	}


	public function popup() {

		$p = $this->input->post(NULL, true);

		$receiver_list = array();
		switch($p['sms_resource']) {
			case 'appointment':
				$this->load->model('patient_model');
				$where = array('pa.no'=>$p['sms']);
				$receiver_rs = $this->patient_model->select_appointment($where, $field="p.name, p.mobile, pa.appointment_date, pa.appointment_time_start, p.manager_team_code");

				foreach($receiver_rs as $k=>$v) {
					$v['mobile_txt'] = $this->common_lib->manufacture_mobile($v['mobile'], $v['manager_team_code']);
					$v['mobile'] = $this->common_lib->manufacture_mobile($v['mobile'], $v['manager_team_code']);				// 20170315 kruddo : sms 발송 번호 오류 수정
					$mk = strtotime($v['appointment_date'].' '.$v['appointment_time_start']);
					$v['date'] = date('m월 d일', $mk);
					$v['time'] = date('H시 i분', $mk);
					$receiver_list[] = $v;
				}
			break;
			case 'teamdb':
				$this->load->model('consulting_model');
				$where = array('cst_seqno'=>$p['sms']);
				$receiver_rs = $this->consulting_model->select_consulting_all($where, 'consulting_info', 'name, tel, team_code');

				foreach($receiver_rs as $k=>$v) {
					$v['mobile_txt'] = $this->common_lib->manufacture_mobile($v['tel'], $v['team_code']);
					$v['mobile'] = $this->common_lib->manufacture_mobile($v['tel'], $v['team_code']);					// 20170315 kruddo : sms 발송 번호 오류 수정
					$mk = strtotime($v['appointment_date'].' '.$v['appointment_time_start']);
					$v['date'] = '';
					$v['time'] = '';
					$receiver_list[] = $v;
				}

			break;
		}

		//pre($receiver_list);


		$label = $this->Sms_model->select_msg_type(array('is_use'=>'Y'));
		$data = array (
			'cfg'=>array(
				'label'=>$label,
				'remain'=>$this->get_count()
			),
			'receiver'=>$receiver_list
		);
		$this->_render('popup', $data, 'blank');
	}

	public function popup_log() {
		$data = array(
			'cfg'=> array(
				'date'=>$this->common_lib->get_cfg(array('date'))
			)
		);

		$this->_render('popup_log', $data, 'blank');
	}

	public function popup_log_inner() {
		$page = $this->input->post('page');
		$limit = 15; //($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		$where_offset = array('user_id'=>$this->session->userdata('ss_user_id'));
		$where = array();
		parse_str($this->input->post('search'), $assoc);
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'reg_date_s':
					$where['reg_date >=']=str_replace('-','',$v).'000000';
				break;
				case 'reg_date_e':
					$where['reg_date <=']=str_replace('-','',$v).'235959';
				break;
				case 'reserve_chk':
					$where[$k]="{$v}";
				break;
				case 'word':
					$where["(p.name LIKE '%{$v}%' OR p.mobile LIKE '%{$v}%' OR p.chart_no LIKE '%{$v}%' )"]=NULL;
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}


		$rs = $this->Sms_model->select_log_paging($where, $offset, $limit, $where_offset);
		//pre($where);

		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			// pre($rs['list']);
			foreach($rs['list'] as $row) {
				$row['regdate'] = date('Y-m-d H:i', strtotime($row['reg_date']));
				$row['mobile'] = '010-****-'.substr($row['receiver'],-4);
				//$row['msg']=nl2br($row['msg']);
				$row['idx'] = $idx;

				$status_arr = explode('|', $row['status']);
				foreach($status_arr as $v) {
					list($field,$value) = explode(':',$v);
					$status[$field] = $value;
				}
				//pre($status);
				$row['status'] = $status;
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



	public function lists() {
		if (isset( $_GET['srch_type'] ) && ! empty( $_GET['srch_type'] )) $where['reserve_chk'] = $_GET['srch_type'];
		if (isset( $_GET['srch_status'] ) && ! empty( $_GET['srch_status'] )) {
			if ($_GET['srch_status'] == "Y") $where['SUBSTRING(status,6,4)'] = '0000';
			else if ($_GET['srch_status'] == "N") $where['SUBSTRING(status,6,4) != '] = '0000';
		}

		$start_date = date( 'Y-m-d' );
		$end_date = date( 'Y-m-d' );

		if ($_GET['srch_start_date'] != '') {
			$start_date = $_GET['srch_start_date'];
			$end_date = $_GET['srch_end_date'];
		}
		$where['reg_date >= '] = str_replace( '-', '', $start_date ) . '000000';
		$where['reg_date <= '] = str_replace( '-', '', $end_date ) . '999999';

		$result = $this->Sms_model->get_list( $where );
		foreach ( $result as $i => $row ) {

			$exp_status = explode( '|', $row['status'] );
			$list[$i]->msg_type = $row['msg_type'];
			$list[$i]->receiver = $row['receiver'];
			$list[$i]->sender = $row['sender'];
			$list[$i]->msg = $row['msg'];
			$list[$i]->reserve_type = ($row['reserve_chk'] == 'Y') ? '예약발송' : '즉시발송';
			$list[$i]->reg_date = substr( $row['reg_date'], 0, 4 ) . '/' . substr( $row['reg_date'], 4, 2 ) . '/' . substr( $row['reg_date'], 6, 2 ) . ' ' . substr( $row['reg_date'], 8, 2 ) . ':' . substr( $row['reg_date'], 10, 2 );

			$status = str_replace( 'msg:', '', $exp_status[1] );
			$list[$i]->status = ($status == '등록성공' || $status == '등록실패') ? str_replace( '등록', '발송', $status ) : $status;
		}
		$selected['srch_type'][$_GET['srch_type']] = 'selected';
		$selected['srch_status'][$_GET['srch_status']] = 'selected';

		$data = array (
				'list'=>$list,
				'selected'=>$selected,
				'start_date'=>$start_date,
				'end_date'=>$end_date
		);
		$this->load->view( 'sms/list', $data );
	}



	public function default_msg() {
		$row = $this->Sms_model->get_msg( $this->input->post( 'category' ) );
		echo $row['msg'];
	}

	public function send_new() {
		$p = $this->input->post(NULL, true);
		$receivers = json_decode($p['receiver'], true);


		//pre($p);exit;
		//pre($receiver);



		$msg_type = strtolower($p['msg_type']);
		$sender = $this->sender;
		$message = str_replace("'","",$p['msg']);
		if($p['use_reserve'] == 'yes') {
			$reserve_time = $p['reserve_date'].' '.$p['reserve_time'];
			$reserve_chk = 'Y';

			//예약시간체크
			$reserve_term = strtotime($reserve_time)-time();
			if($reserve_term <= 3600) {
				return_json(false, '예약발송은 1시간 이후부터 가능합니다.');
			}
		}
		else {
			$reserve_time = '';
			$reserve_chk = 'N';
		}

		$map = ($p['use_map']=='yes')?'http://cmltd.kr/images/common/sms_map_20170928.jpg':'';

		$success = $failure = 0;
		foreach($receivers as $row){
			$receiver = str_replace('-','',$row['mobile']);

			$msg = str_replace(array('[이름]','[예약일]','[예약시각]'), array($row['name'],$row['date'],$row['time']), $message);


			$result = $this->sms_lib->sms_send($msg_type, $receiver, $msg, $sender, $map, $reserve_time, NULL, NULL, NULL, $reserve_chk);
			$status = $result['code'];

			$input = null;
			$input['msg_type'] = $msg_type;
			$input['receiver'] = $receiver;
			$input['receiver_name'] = $row['name'];
			$input['sender'] = $sender;
			$input['msg'] = $msg;
			$input['reserve_time'] = $reserve_time;
			$input['reserve_chk'] = $reserve_chk;
			$input['reg_date'] = TIME_YMDHIS;
			$input['status'] = $result;
			$input['user_id'] = $this->session->userdata( 'ss_user_id' );
			$input['biz_id'] = $this->session->userdata( 'ss_biz_id' );
			$input['hst_code'] = $this->session->userdata( 'ss_hst_code' );

			$this->Sms_model->insert_log($input);

			$exp_result = explode( '|', $result );
			$code = str_replace('code:', '', $exp_result[0]);
			if ($code == '0000') $success ++;
			else $failure++;
		}

		$send_txt = ($p['use_reserve']=='yes')?'발송예약':'발송';
		if($failure<1) {
			$send_msg = "총 {$success}의 문자가 정상적으로 {$send_txt}되었습니다.";
		}
		else if($success<1) {
			$send_msg = "문자{$send_txt}에 실패하였습니다.";
		}
		else {
			$send_msg = "정상{$send_txt} : {$success}건 | 실패{$send_txt} : {$failure}건";
		}
		return_json(true, $send_msg);
	}

	private function get_count() {
		//팀별 sms발송 갯수 제한 by 이혜진 2015-08-07
		$max = 1500;					// 20170317 kruddo : SMS 제한 700건->1500건으로 늘림
		$team = $this->session->userdata("ss_team_code");
		$team_user = $this->common_lib->get_user($team);
		$month_start = date('Ym01000000');
		$month_end = date('YmdHis');

		//기존 발송갯수 체크
		$count = $this->Sms_model->count_log(array('user_id'=>array_keys($team_user), 'reg_date >= '=>$month_start, 'reg_date <= '=>$month_end));

		return $this->max-$count;
	}

	public function send() {
		//팀별 sms발송 갯수 제한 by 이혜진 2015-08-07
		$max = 1500;					// 20170317 kruddo : SMS 제한 700건->1500건으로 늘림
		$team = $this->session->userdata("ss_team_code");
		$team_user = $this->common_lib->get_user($team);
		$month_start = date('Ym01000000');
		$month_end = date('YmdHis');

		//기존 발송갯수 체크
		$count = $this->Sms_model->count_log(array('user_id'=>array_keys($team_user), 'reg_date >= '=>$month_start, 'reg_date <= '=>$month_end));

		$receiver = $this->input->post( 'receiver' );
		if($max <= $count) {
			echo "이번달 발송가능 갯수를 모두 사용하셨습니다.";
			exit;
		}
		if($max < $count+count($receiver)) {
			echo "이번달 발송 가능한 잔여SMS는 ".($max-$count)."개입니다.";
			exit;
		}


		$success_total = 0;
		foreach ( $this->input->post( 'receiver' ) as $i => $val ) {

			$msg_type = strtolower( $this->input->post( 'msg_type' ) );
			$receiver = $val;
			$sender = str_replace( '-', '', $this->input->post( 'sender' ) );
			// $msg = htmlspecialchars( $this->input->post( 'msg' ) );
			$msg = str_replace( "'", "", $this->input->post( 'msg' ) );
			$reserve_time = ($this->input->post( 'chk' ) == 'Y') ? $this->input->post( 'send_date' ) . ' ' . str_pad( $this->input->post( 'send_hour' ), 2, '0', STR_PAD_LEFT ) . ':' . str_pad( $this->input->post( 'send_minute' ), 2, '0', STR_PAD_LEFT ) . ':00' : NULL;
			$reserve_chk = ($this->input->post( 'chk' ) == 'Y') ? 'Y' : 'N';
			$msg_type = ($msg_type == 'lms') ? 'mms' : $msg_type;
			$reserve_time = trim( $reserve_time );

			// $map = ($this->session->userdata( 'ss_biz_id' ) == 'klasseps' && $_POST['category'] == 0)? 'http://klasseps.com/images/quick/map_details.jpg':'';
			$map = ($this->session->userdata( 'ss_biz_id' ) == 'HBPS' && $_POST['category'] != '5')? 'http://cmltd.kr/images/common/sms_map_20170928.jpg':'';

			if ($map != '') $msg_type = 'mms';

			$result = $this->sms_lib->sms_send( $msg_type, $receiver, $msg, $sender, $map, $reserve_time, NULL, NULL, NULL, $reserve_chk );
			$status = $result['code'];

			$input = null;
			$input['msg_type'] = $msg_type;
			$input['receiver'] = $receiver;
			$input['sender'] = $sender;
			$input['msg'] = $msg;
			$input['reserve_time'] = $reserve_time;
			$input['reserve_chk'] = $reserve_chk;
			$input['reg_date'] = TIME_YMDHIS;
			$input['status'] = $result;
			$input['user_id'] = $this->session->userdata( 'ss_user_id' );
			$input['biz_id'] = $this->session->userdata( 'ss_biz_id' );
			$input['hst_code'] = $this->session->userdata( 'ss_hst_code' );

			$this->Sms_model->insert_log( $input );

			$exp_result = explode( '|', $result );
			$code = str_replace( 'code:', '', $exp_result[0] );

			if ($code == '0000') $success_total ++;
		}

		if ($reserve_chk == 'Y') $msg = '총 ' . $success_total . '건의 문자가 정상적으로 예약되었습니다';
		else $msg = '총 ' . $success_total . '건의 문자가 정상적으로 발송되었습니다';

		echo $msg;
	}



	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "/sms/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
