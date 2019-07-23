<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consulting extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->param = $this->input->post(NULL, true);
		$this->path_list = $this->config->item( 'all_path' );
		$this->category = $this->config->item('category');

		$this->load->model( array (
			'User_m',
			'Consulting_m',
			'Manage_m'
		) );

		$this->load->library( array (
			'consulting_lib'
		) );

		$this->cst_status_list = $this->config->item( 'cst_status' );

	}

	public function index() {
		$this->lists();
	}

	public function lists() {
		$assign = array();
		$this->_display('lists', $assign, 'sub');
	}

	function consulting_list_paging($assoc='') {
		$p = $this->param;

		$this->load->library('patient_lib');

		if(empty($assoc)) {
			parse_str($this->input->post('search'), $assoc);
		}

		$_SESSION['search'] = array_filter($assoc); //검색데이터 세션처리

		$type = $p['type']; //share:공동DB, 99:재진관리, cpa:CPA내역
		$page = ($assoc['page'])?$assoc['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		$where_offset = array(
			'use_flag'=>'Y',
			'hst_code'=>$this->session->userdata('ss_hst_code'),
			'biz_id'=>$assoc['grant_biz']
		);



		if ($this->session->userdata( 'ss_dept_code' ) == '90') {
			$where_offset['team_code'] = $this->session->userdata('ss_team_code');
			$order_by = "accept_flag DESC, cst_seqno DESC";
		}
		else {
			$order_by = "cst_seqno DESC";
		}

		$where_offset['charge_date >= ']=date('YmdHis');


		$where = array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('type','limit', 'page', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( name LIKE '%{$v}%' OR tel LIKE '%{$v}' OR messenger LIKE '%{$v}%' )"]=NULL;
				break;

				default :
					if(substr($v,0,2) == '!=') {
						$where[$k.$v] = null;
					}
					else {
						$where[$k] = $v;
					}
					break;
			}
		}

		$team_list = $this->User_m->get_team_list( '90' );
		$main_category = $this->Manage_m->get_code_item( '01' );
		$sub_category = $this->Manage_m->get_code_item( '02' );
		$user_list = $this->User_m->get_team_user();


		$rs = $this->Consulting_m->select_consulting_paging($where, $offset, $limit, $where_offset, $order_by);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				//유효기간
				if ($type == '') {
					$charge_date = ($row['charge_date'] == '99999999999999')?'-' : date('Y-m-d',strtotime($row['charge_date']));
				}

				//메모
				$memo = $this->Consulting_m->select_consulting(array('cst_seqno'=>$row['cst_seqno']), 'seqno, memo', 'seqno DESC','', 'consulting_memo');
				if(empty($memo)) $memo = array();

				//db보기권한 -  팀DB에서 공동DB로 최초 이동된후 +7일간 초기분배팀에 보기권한없음 by 이혜진 20150511
				if($type == 'share') {
					$grant_view = $this->consulting_lib->check_grant_share($row['cst_seqno'], $row['charge_date']);
				}
				else {
					$grant_view = 'Y';
				}

				//전화번호
				$tel = $row['tel'];;
				if($type == 'share') {
					$tel = $this->common_lib->manufacture_mobile($tel,'','ex_phone2');
				}
				else {
					$tel = $this->common_lib->manufacture_mobile($tel, $row['team_code'],'ex_phone2');
				}

				$row['reg_date'] = date('m/d H:i', strtotime($row['reg_date']));
				$row['grant_view']=$grant_view;

				// pre($row);
				$birth = (substr($row['birth'],0,1) >= 3)?'19'.$row['birth']:$row['birth'];
				$age = date('Y')-substr($birth,0,4)+1;


				$row['tel']=$tel;

				switch ($row['cpa']) {
					case 'valid':
						$cpa_mark = '<i class="fa fa-circle-o fc-theme"></i>';
					break;
					case 'invalid':
						$cpa_mark = '<i class="fa fa-times"></i>';
					break;
					default:
						$cpa_mark = '';
					break;
				}
				$row['cpa_mark'] = $cpa_mark;

				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		//매출총액
		$sum_field = "SUM(patient_sales) AS sales_total";
		$sum = $this->Consulting_m->select_consulting_row(array_merge($where, $where_offset), 'consulting_info', $sum_field);



		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>5,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$return = array(
			'sum'=>$sum,
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		if($this->output=='excel') {
			return $return;
		}
		else {
			if($rs['count']['search']>0) {
				return_json(true, '', $return);
			}
			else {
				return_json(false, '', $return);
			}
		}

	}

	public function input($cst_seqno) {
		$this->load->library('patient_lib');

		$main_category = $this->Manage_m->get_code_item( '01' );
		$job_list = $this->Manage_m->get_code_item( '05' );
		$this->team_list = $this->User_m->get_team_list( '90' );

		$row = $this->Consulting_m->get_cst_info( $cst_seqno );

		//권한체크
		$type = $this->consulting_lib->check_type($row['charge_date']);

		if($type == 'share') {
			$grant_view = $this->consulting_lib->check_grant_share($row['cst_seqno'], $row['charge_date']);
		}
		else {
			$grant_view = 'Y';
		}

		if($grant_view=='N') {
			alert('이 데이터(DB)를 볼 수 있는 권한이 없습니다.');
			exit;
		}

		// 접수일 업데이트
		if ($row['accept_date'] == '' && $row['team_code'] == $this->session->userdata( 'ss_team_code' )) {
			$input = null;
			$input['accept_date'] = TIME_YMDHIS;
			$this->Consulting_m->update_cst( $cst_seqno, $input );
		}

		$type = $this->consulting_lib->check_type($row['charge_date']);
		$this->page_title = ($type == 'share')? '공동 DB':'팀 DB';
		$select_date_valid = $this->consulting_lib->check_appointment_cnt($type, $row['appointment_cnt']);

		// 권한 체크
		$valid = true;
		if ($this->session->userdata( 'ss_dept_code' ) == '90') {
			$valid = $this->have_auth( $type, $row );

			if (! $valid) {
				alert( '정상적인 경로가 아닙니다.' );
			}
		}



		//최초팀의 경우 공동DB데이터를 만료후 7일간 볼수 없음 TODO
		$row['birth'] = ($row['birth'] == 0) ? '' : $row['birth'];
		$row['path_txt'] = $this->path_list[$row['path']];
		$row['reg_date_txt'] = date('Y-m-d H:i', strtotime($row['reg_date']));

		/*
		if ($this->consulting_lib->team_name != '') {
			$row['team_code'] = $this->consulting_lib->contact_team_code;
			$row['team_name'] = $this->consulting_lib->contact_team_name;
			$row['charge_user_id'] = $this->consulting_lib->contact__user_id;
		} else {
			$row['team_name'] = $this->team_list[$row['team_code']];
		}
		*/



		//$row['tel'] = ($this->_valid_view_tel())? tel_check( $row['tel'], '-' ):set_blind( 'phone', tel_check( $row['tel'], '-' ) );
		$row['tel'] = $this->common_lib->manufacture_mobile($row['tel'], $row['team_code'],'ex_phone2');

		if ($type == 'share') {
			if ($this->session->userdata( 'ss_dept_code' ) == '90') {
				$contact_row = $this->Consulting_m->get_contact_info( $cst_seqno );
				$row['contact_date'] = dead_line( $contact_row['contact_date'] );
				$row['contact_seqno'] = $contact_row['seqno'];
			}
		}




		//저장권한
		$save_valid = false;
		$result = $this->Manage_m->get_access_list(2);
		foreach ( $result as $i => $access_row ) {
			${'access_' . $access_row['category']}[$access_row['valid_code']] = $access_row['valid_code'];
		}

		if($this->common_lib->check_auth_group('save_consulting')) {
			$save_valid = true;
		}

		/*
		if(in_array($this->session->userdata( 'ss_dept_code' ), array('90', '10'))) {

		} else if (in_array( $this->session->userdata( 'ss_dept_code' ), $access_dept)) {
			$save_valid = true;
		} else if (in_array( $this->session->userdata( 'ss_team_code' ), $access_team)) {
			$save_valid = true;
		} else if (in_array( $this->session->userdata( 'ss_duty_code' ), $access_duty)) {
			$save_valid = true;
		}
		*/

		$media = $this->common_lib->api('media');

		$assign = array (
			'cfg'=>array(
				'path'=>$this->path_list,
				'media'=>$media,
			),
			'row'=>$row,
			'main_category'=>$main_category,
			'job_list'=>$job_list,
			'cst_status_list'=>$this->cst_status_list,
			'team_list'=>$this->team_list,
			'type'=>$type,
			'save_valid'=>$save_valid,
			'select_date_valid'=>$select_date_valid,
			'auth'=>array(
				'm_chargedate'=>$this->common_lib->check_auth_group('m_chargedate'),
				'path_change'=>$this->common_lib->check_auth_group('path_change'),
				'ex_phone2'=>$this->common_lib->check_auth_group('ex_phone2')
			)
		);

		//pre($assign);
		$this->_display('input', $assign, 'sub');
	}

	/**
	 * DB신규입력
	 * @return [type] [description]
	 */
	public function db_input() {
		$path = $this->config->item('path');
		$media = $this->common_lib->api('media');

		$assign = array(
			'cfg'=>array(
				'path'=>$path,
				'media'=>$media,
				'category'=>$this->category,		// 20170320 kruddo : 상담항목 추가
			)
		);
		$this->_display('db_input', $assign, 'sub');
	}

	public function memo() {
		$cst_seqno = $this->param['cst_seqno'];
		$result = $this->Consulting_m->get_memo_list( $cst_seqno );
		$memo_list = array();
		foreach ( $result as $i => $memo_row ) {
			$memo_list[$i]['memo'] = $memo_row['memo'];
			$memo_list[$i]['reg_date'] = date('Y-m-d H:i:s', strtotime($memo_row['reg_date']));
			$memo_list[$i]['reg_user_id'] = $memo_row['reg_user_id'];
			$memo_list[$i]['name'] = $memo_row['name'];
		}


		$assign = array(
			'memo'=>$memo_list
		);
		$this->_display('memo', $assign, 'inc');
	}

	private function have_auth($type, $row) {

		$CI = & get_instance();
		$CI->load->model( 'Consulting_m' );

		$limit_date = date( 'YmdHis', mktime( date( 'H' ), date( 'i' ) - 30, 0, date( 'm' ), date( 'd' ), date( 'Y' ) ) );

		$is_valid = false;

		if ($type == 'share') {

			$charge_user_id = $CI->Consulting_m->get_valid_contact( $row['cst_seqno'], $limit_date );

			if ($CI->session->userdata( 'ss_user_id' ) == $charge_user_id) {

				$is_valid = true;

			} else if ($charge_user_id == '') {

				$contact_row = $CI->Consulting_m->my_valid_contact( $limit_date );

				if (! empty( $contact_row['cst_seqno'] ) && ($row['cst_seqno'] != $contact_row['cst_seqno'])) {

					$url = $_SERVER['HTTP_HOST'] . '/consulting/input/' . $contact_row['cst_seqno'];
					$msg = '현재 진행중인 상담건이 있습니다.';
					alert( $msg, $url );

				} else {

					$CI->Consulting_m->contact_insert( $row['cst_seqno'] );

					$input = null;
					$input['charge_user_id'] = $CI->session->userdata( 'ss_user_id' );
					$input['team_code'] = $CI->session->userdata( 'ss_team_code' );
					$CI->Consulting_m->update_cst( $row['cst_seqno'], $input );


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


	private function _display($tpl, $assign, $layout="sub") {
		$this->layout_lib->default_('consulting/'.$tpl.'.html', $assign, $layout);
		$this->layout_lib->print_();
	}
}
