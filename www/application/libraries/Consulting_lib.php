<?php
/**
 * 작성 : 2014.11.26
*  수정 : 2015.02.06
*  수정 : 2015.02.25 -DB 규칙 개정 관련 (2월17일 개정안)
*
* @author 이미정
*/
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Consulting_lib {

	var $charge_user_id = '';
	var $contact_team_name = '';
	var $contact_team_code = '';
	var $filter_limit = -1;

	private $ci;

	function __construct() {
		$this->ci =& get_instance();
	}


	public function check_charge($row, $cst_status, $post) {

		$year = date('Y');
		$month = date('m');
		$day = date('d');
		$hour = date('H');
		$minute = date('i');

		$filter = $this->_check_filter($row['cst_seqno']);
		$data = null;
		$data['filter'] = $filter;
		$data['charge_date'] = null;


		if (in_array( $cst_status, array ('00') )) { //종료(00)
			$data['charge_date'] = TIME_YMDHIS;
			return $data;
		}


		//공동DB
		if ($row['charge_date'] < TIME_YMDHIS) {
			if (in_array($cst_status, array('51', '52'))) { //내원예약(51), 내원상담(52)
				$data['charge_date'] = date('YmdHis', strtotime('+6 months'));
				return $data;
			}
		}

		if ($filter > $this->filter_limit) {
			if (in_array($cst_status, array('13', '98', '99'))) { //예치금(13), 수술예정(98), 수술완료(99)
				$data['charge_date'] = PERMANANT_DATE;
			}
			else {
				$data['charge_date'] = $row['charge_date'];
				// $reg_date = strtotime($row['reg_date']);
				// $data['charge_date'] = date('YmdHis', strtotime('+30 days', $reg_date));
			}
		}



		if ($data['charge_date'] == PERMANANT_DATE)	$data['permanent_status'] = 'N';
		else $data['charge_date'] = ($row['charge_date'] > $data['charge_date'])? $row['charge_date']:$data['charge_date'];




			/*
			if ($cst_status == '51') { //내원예약
				$exp_date = explode('-', $post['appointment_date']);
				$data['charge_date'] = date( "YmdHis", mktime( $hour, $minute, 0, $exp_date[1], $exp_date[2] + 7, $exp_date[0] ) );
			}
			else if (in_array( $cst_status, array ('52','90') )) { //내원상담,수술취소

				$data['charge_date'] = date( "YmdHis", mktime( $hour, $minute, 0, $month, $day + 7, $year ) );
			}
			else if ($cst_status == '13') { //예치금
				$data['charge_date'] = date( "YmdHis", mktime( $hour, $minute, 0, $month, $day + 30, $year ) );
			}
			else if ($cst_status == '98') { //수술예정
				$exp_date = explode('-', $post['plan_date']);
				$data['charge_date'] = date( "YmdHis", mktime( $hour, $minute, 0, $exp_date[1], $exp_date[2] + 7, $exp_date[0] ) );
			} else if ($cst_status == '99') {
				$data['charge_date'] = PERMANANT_DATE;
			}
			else {
				//전화(T), 워킹(W)
				if(in_array($row['path'], array('T','W'))) {
					$data['charge_date'] = date( "YmdHis", strtotime('+30 days'));
				}
			}
			*/

		return $data;
	}


	function trans_media($media, $reverse=false) {
		$code = $this->ci->common_lib->get_code_info(array('title'=>$media,'group_code'=>'04'));
		if(empty($code)) return $media;

		if($reverse) {
			$result = array_values($this->ci->common_lib->get_code(array('parent_code'=>$code['code'])));
		}
		else {
			$code_all = $this->ci->common_lib->get_code_item('04');
			$result = $code_all[$code['parent_code']];
		}

		return $result;
	}


	public function set_charge_date($path) {
		$year = date('Y');
		$day = date('d');
		$hour = date('H');
		$minute = date('i');
		$month = date('m');

		if (in_array( $path, array ('E', 'Y') )) { //E:기존고객, Y: 지인소개(실장/고객)
			$charge_date = PERMANANT_DATE;
		} else {
			$charge_date = date('YmdHis', strtotime('+6 months'));
		}

		// if (in_array( $path, array ('T','W'	) )) { //T:전화 , W:워킹
		// 	$charge_date = date( "YmdHis", mktime( $hour, $minute, 0, $month, $day + 3, $year ) );
		// } else if (in_array( $path, array ('C','P') )) { // C:카카오톡, P:실시간상담
		// 	$charge_date = date( "YmdHis", mktime( $hour, $minute, 0, $month, $day + 30, $year ) );
		// } else if (in_array( $path, array ('E', 'Y') )) { //E:기존고객, Y: 지인소개(실장/고객)
		// 	$charge_date = PERMANANT_DATE;
		// } else {
		// 	$charge_date = date( "YmdHis", mktime( $hour, $minute, 0, $month, $day + 30, $year ) );
		// }


		// $charge_date = PERMANANT_DATE; //#354

		return $charge_date;
	}



	private function _check_filter($cst_seqno) {
		$CI = & get_instance();
		$CI->load->model( 'Consulting_model' );

		$field = "(if(length(`name`) > 0, 1, 0)
			+ if(length(`birth`) > 0 || length(`age`) > 0, 1, 0)
			+ if(length(`sex`) > 0, 1, 0)
			+ if(length(`tel`) > 0, 1, 0)
			+ if(length(`messenger`) > 0, 1, 0)
			+ if(length(`email`) > 0, 1, 0)
			+ if(length(`call_time`) > 0, 1, 0)
			+ if(length(`addr`) > 0, 1, 0)
			+ if(length(`character`) > 0, 1, 0)
			+ if(length(`etc`) > 0, 1, 0)
			+ if(length(`cost`) > 0, 1, 0)
			+ if(length(`other_status`) > 0, 1, 0)
			+ if(length(`reoper_cnt`) > 0, 1, 0)
			+ if(length(`oper_date`) > 0, 1, 0)
			+ if(length(`job_code`) > 0, 1, 0)
			+ if(length(`treat_cost_no`) > 0, 1, 0)
			+ if(length(`treat_cost_no_interest`) > 0, 1, 0)
		) as filter";
		$row = $CI->Consulting_model->get_cst_info($cst_seqno, $field);

		return $row['filter'];
	}




	public function have_auth($type, $row) {

		$CI = & get_instance();
		$CI->load->model( 'Consulting_model' );

		$limit_date = date( 'YmdHis', mktime( date( 'H' ), date( 'i' ) - 30, 0, date( 'm' ), date( 'd' ), date( 'Y' ) ) );

		$is_valid = false;

		if ($type == 'share') {

			$charge_user_id = $CI->Consulting_model->get_valid_contact( $row['cst_seqno'], $limit_date );

			if ($CI->session->userdata( 'ss_user_id' ) == $charge_user_id) {

				$is_valid = true;

			} else if ($charge_user_id == '') {

				$contact_row = $CI->Consulting_model->my_valid_contact( $limit_date );

				if (! empty( $contact_row['cst_seqno'] ) && ($row['cst_seqno'] != $contact_row['cst_seqno'])) {

					$url = $_SERVER['HTTP_HOST'] . '/consulting/input/' . $contact_row['cst_seqno'];
					$msg = '현재 진행중인 상담건이 있습니다.';
					alert( $msg, $url );

				} else {

					$CI->Consulting_model->contact_insert( $row['cst_seqno'] );

					$input = null;
					$input['charge_user_id'] = $CI->session->userdata( 'ss_user_id' );
					$input['team_code'] = $CI->session->userdata( 'ss_team_code' );
					$CI->Consulting_model->update_cst( $row['cst_seqno'], $input );


					$this->contact_user_id = $input['charge_user_id'];
					$this->contact_team_name = $this->team_list[$input['team_code']];
					$this->contact_team_code = $input['team_code'];

					$is_valid = true;
				}

			} else {

				$msg = '현재 ' . $charge_user_id . '가 컨택중입니다.';
				alert( $msg );
			}
		} else {
			//if (in_array( $CI->session->userdata( 'ss_position_code' ), array (	'51','52' ) ) && $CI->session->userdata( 'ss_team_code' ) == $row['team_code']) $is_valid = true;
			if ($CI->session->userdata( 'ss_team_code' ) == $row['team_code']) $is_valid = true;
			else $is_valid = false;
		}
		return $is_valid;
	}

	public function check_appointment_cnt($type, $appointment_cnt) {
		$select_date_valid = ($type != 'share' && $appointment_cnt >= 2)? 'N':'Y';
		return $select_date_valid;
	}


	public function check_type($charge_date) {
		$type = '';
		if ($charge_date < TIME_YMDHIS) {
			$type = 'share';
		}

		return $type;
	}

	public function check_grant_share($cst_seqno, $charge_date) {

		$this->ci->load->model( 'Consulting_model' );

		$grant_view = 'Y';
		$last_log = $this->ci->Consulting_model->charge_log_last(array('cst_seqno'=>$cst_seqno)); //최종변경로그
		if($last_log && $last_log['team_code'] == $this->ci->session->userdata('ss_team_code')) {
			$count_move = $this->ci->Consulting_model->charge_log_count(array('cst_seqno'=>$cst_seqno, 'act'=>'move'));
			$count_first = $this->ci->Consulting_model->charge_log_count(array('cst_seqno'=>$cst_seqno, 'act'=>'first'));
			$first_log = $this->ci->Consulting_model->charge_log_last(array('cst_seqno'=>$cst_seqno, 'act'=>'first')); //최종변경로그
			$term = floor((time()-strtotime($charge_date))/(60*60*24));
			if($count_move>0) {//두번이상이동

				if($term > 7 && $term <=14) {
					// $grant_view = 'N';
				}
			}
			else { //최초이동
				if($count_first>0 && $term <= 7 && $first_log['team'] == $this->ci->session->userdata('ss_team_code')){
					$grant_view = 'N';
				}
			}
		}
		return $grant_view;
	}

	/**
	 * 데이터 변경이력
	 * @param  array $old [description]
	 * @param  array $new [description]
	 * @return int      [description]
	 */
	public function save_log($old, $new) {
		$CI = $this->ci;
		$CI->load->model(array('User_Model','Manage_Model','Consulting_model', 'Treat_Model'));

		$fields = array(
			'name'=>'이름',
			'sex'=>'성별',
			'birth'=>'생년월일',
			'age'=>'나이',
			'tel'=>'연락처',
			'messenger'=>'메신저',
			'email'=>'이메일',
			'call_time'=>'통화가능시간',
			'addr'=>'거주지',
			'job_code'=>'직업',
			'character'=>'고객성향',
			'etc'=>'메모',
			'treat_cost_no'=>'진료항목',
			'treat_cost_no_interest'=>'관심항목',
			'cost'=>'예상비용',
			'other_status'=>'타병원상담',
			'reoper_cnt'=>'재수술횟수',
			'oper_date'=>'수술예정시기',
			'refund_type'=>'환불유형',
			'refund'=>'환불금액',
			'team_code'=>'팀',
			'charge_user_id'=>'내원담당자',
			'closing_user_id'=>'클로징담당자',
			// 'sales'=>'매출',
			'complain_status'=>'컴플레인',
			'cst_status'=>'상태',
			'cst_status_reason'=>'상태부가정보',
			'appointment_date'=>'내원예약일',
			'appointment_time'=>'내원예약시각',
			'plan_date'=>'수술예정일',
			'plan_time'=>'수술예정시각',
			'surgery_date'=>'수술완료일',
			'locale'=>'수술지역'
			// 'sales_origin'=>'정상수가'
		);

		$change_list = array();
		foreach($new as $k=>$v) {
			if($old[$k]==$v) continue;
			if(!array_key_exists($k, $fields)) continue;
			$change_list[$k]['title'] = $fields[$k];

			$value_old = $old[$k];
			$value_new = $v;
			$value_cfg = array();
			switch ($k) {
				case 'cst_status': //상태
					$value_cfg = $CI->config->item( 'cst_status' );
				break;
				case 'team_code': //팀
					$value_cfg = $CI->User_Model->get_team_list( '90' );
				break;
				case 'charge_user_id': //담당자
				case 'closing_user_id': //담당자
					$value_cfg = $CI->User_Model->get_team_user();
				break;
				case 'sex': //성별
					$value_cfg = array('M'=>'남','F'=>'여');
				break;
				case 'job_code': //직업
					$value_cfg = $CI->Manage_Model->get_code_item( '05' );
				break;
				case 'treat_cost_no': //진료항목
					$value_cfg = $CI->Treat_Model->select_cost_field(array('depth'=>3), 'name');
				break;
				case 'treat_cost_no_interest': //관심항목
					$arr = explode(',',$v);
					$rs = $CI->Treat_Model->select_cost_field(array('no'=>$arr), 'name');
					// pre($rs);
					$value_new = implode(',', array_values($rs));
					// $value_new =

				break;
				case 'refund_type':
					$value_cfg = array('N'=>'환불안함','D'=>'예치금환불','O'=>'수술비환불');
				break;
				case 'locale':
					$value_cfg = array('ko'=>'한국', 'cn'=>'중국');
				break;
				default:
				break;
			}

			$value_old = (array_key_exists($value_old, $value_cfg))?$value_cfg[$value_old]:$value_old;
			$value_new = (array_key_exists($value_new, $value_cfg))?$value_cfg[$value_new]:$value_new;

			$change_list[$k]['old'] = $value_old;
			$change_list[$k]['new'] = $value_new;

		}

		$total = 0;
		foreach ( $change_list as $k => $v ) {
			$input = null;
			$input['title'] = $v['title'];

			$value_old =($v['old'])?$v['old']:'(내용없음)';
			$value_new =($v['new'])?$v['new']:'(내용없음)';

			$contents = $value_new; //$value_old.' => '.
			// $contents .= ($v['old'])?"(".$v['old'].")":'';
			$input['contents'] = (empty($contents))?'&nbsp;':$contents;

			$input['reg_date'] = TIME_YMDHIS;
			$input['reg_user_id'] = $CI->session->userdata( 'ss_user_id' );
			$input['cst_seqno'] = $CI->input->post( 'cst_seqno' );
			$input['team_code'] = $CI->session->userdata( 'ss_team_code' );
			// pre($input);
			$CI->Consulting_model->log_insert( $input );
			$total ++;
		}

		return $total;
	}
}
