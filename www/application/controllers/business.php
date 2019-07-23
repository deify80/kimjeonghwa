<?php
ini_set("display_errors", 1);
// error_reporting(E_ALL);
/**
 * 작성 : 2014.12.09
 * 수정 : 2015.03.04 - 이미정 수정
 * @author 우석진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Business extends CI_Controller {
	var $data = null;
	//var $aSettleType = array(0=>"기안서",1=>"지출결의서",2=>"입출금예상안",4=>"일일매출보고",5=>"주간매출보고",3=>"근태신청서");		// 20170322 kruddo : 기안 작성 - 기안 종류 수정
	var $aSettleType = array(0=>"기안서",1=>"지출결의서");																				// 20170322 kruddo : 기안 작성 - 기안 종류 수정

	var $aSettleStatus = array(0=>"대기",1=>"확인",2=>"승인",3=>"반려",4=>"취소",5=>"종료");
	var $aSettleExpenseType = array(0=>"현금",1=>"카드",2=>"계좌이체",3=>"기타");
	var $aSettleExpenseDevision = array(0=>"복리후생비",1=>"접대비",2=>"기타");
	var $aSettleExpenseHoliday = array(0=>"월차",1=>"연차",2=>"반차",3=>"경조사",4=>"대체휴무",5=>"기타");
	var $aSettleExpenseRoute = array(0=>"랜딩",1=>"전화",2=>"워크인",3=>"에이전시",4=>"지인소개(직원)",5=>"지인소개(고객)",6=>"카카오톡",7=>"톡플러스",8=>"홈페이지",9=>"메신저(중국)");

	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'Business_model'
		) );

		$this->load->library('Func_lib');

		$this->aSettleExpenseDoctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));//의사
	}

	public function _remap($method) {
		$this->yield = TRUE;
		$this->segs = $this->uri->segment_array();

		if (method_exists( $this, '_' . $method )) {
			$view = $this->{'_' . $method}();
			if (! empty( $view )) $this->load->view( $view, $this->data );
		} else {
			$this->$method();
		}
	}

	// 기안 작성
	public function _settle_view() {

		$settle_no = $this->segs[4];
		//$view_mode = $this->segs[3];
		$mode = $this->input->get( 'mode' );

		$view_data = $this->Business_model->get_settle_view( $settle_no );
		$view_data['settle_type_str'] = $this->aSettleType[$view_data['settle_type']];

		$settle_draft = $this->Business_model->get_settle_draft( $settle_no );

		// 결재 라인
		$approval = $this->Business_model->get_settle_person( $settle_no, 0 );
		

		//$this->func_lib->pr( $approval ); exit;

		$settle_status_chk = true;
		$instead = $this->Business_model->select_person_instead(array('instead_no'=>$this->session->userdata('ss_user_no')));
		$instead_arr = explode(',',$instead['users']);

		foreach( $approval as $k=>$row ){

			if($view_data['biz_id'] == 'REVIVE' && $row['user_no'] == '1') {
				$approval[$k]['position_title'] = '대표이사';
			}
			



			if( $row['status'] != 0 ){
				$settle_status_chk = false; //결제진행여부체크
			}

			//전단계 결제완료(승인)
			if($status_pre==2 && array_search($row['user_no'], $instead_arr)!==false) {
				$approval[$k]['instead']=true;
			}

			$status_pre = $row['status']; //전단계 결제상태
		}
		// 참조
		$refer = $this->Business_model->get_settle_person( $settle_no, 1 );
		//pre($this->session->userdata);
		if ( $this->session->userdata( 'ss_team_code' ) != '10' && $this->session->userdata('ss_team_code')!='43' && $this->session->userdata('ss_duty_code')!='8' && $this->session->userdata('ss_team_code')!='32' ) { //대표이사,개발팀,총괄,인사팀
			//if($view_data[''] == 'Jhlee')}
			if($view_data['user_no'] == '174' && $this->session->userdata('ss_user_no')=='80') {

			}
			else if($this->session->userdata('ss_user_no') == '192' && in_array($view_data['user_no'], array('127','174'))) {

			}
			else {
				$this->_check_auth($view_data, $approval, $refer);
			}
		}
		else {
			//인사팀장 문서인경우 김혜원팀장(106) 작성 기안은 볼수 없음
			if($view_data['user_no'] =='3' && $this->session->userdata('ss_user_no')=='106') {
				$this->_check_auth($view_data, $approval, $refer);
			}
		}

		// 파일
		$settle_file = $this->Business_model->get_settle_file( $settle_no );

		//수정권한
		$business_mod = $this->common_lib->check_auth_group('business_mod');
		if(!$business_mod) {
			if($view_data['tmp_yn']=='Y') $business_mod = true;
			//기존수정조건건
			else if( ( $this->session->userdata( 'ss_user_no' ) == $view_data['user_no'] ) && ( $mode != "draft" ) && $settle_status_chk) $business_mod = true;
		}

		// 기안 추가 정보

		$settle_expense = array(
			$this->Business_model->get_settle_expense(array('s.settle_no'=>$settle_no, 's.expense_type'=>1)),
			$this->Business_model->get_settle_expense(array('s.settle_no'=>$settle_no, 's.expense_type'=>2)),
			$this->Business_model->get_settle_expense(array('s.settle_no'=>$settle_no, 's.expense_type'=>3)),
			$this->Business_model->get_settle_expense(array('s.settle_no'=>$settle_no, 's.expense_type'=>4))
		);



		// 참고 내용
		$settle_consult = $this->Business_model->get_settle_consult($settle_no);


		$biz_info = $this->Business_model->get_biz_list();
		$settle_tmp_list = $this->Business_model->get_settle_tmp_list();

		$datas = array(
			'cfg'=>array(
				'attendance_settle'=>$this->config->item('attendance_settle'),
				'attendance'=>$this->config->item('attendance'),
			),
			'view_data' => $view_data,
			'approval' => $approval,
			'refer' => $refer,
			'biz_info' => $biz_info,
			'settle_tmp_list' => $settle_tmp_list,
			'settle_type' => $this->aSettleType,
			'settle_status' => $this->aSettleStatus,
			'settle_expense_route' => $this->aSettleExpenseRoute,
			'settle_expense_doctor' => $this->aSettleExpenseDoctor,
			'settle_expense_holiday' => $this->aSettleExpenseHoliday,
			'settle_expense_type' => $this->aSettleExpenseType,
			'settle_expense_devision' => $this->aSettleExpenseDevision,
			'advice_person' => $advice_person,
			'settle_file' => $settle_file,
			'settle_expense' => $settle_expense,
			'settle_expense_stack'=>$settle_expense_stack,
			'settle_consult' => $settle_consult,
			'settle_draft' => $settle_draft,
			'mode' => $mode,
			'settle_status_chk' => $settle_status_chk,
			'wait_list'=>$wait_list,
			'auth'=>array(
				'business_mod'=>$business_mod//수정권한
			)
		);

//return_json(true, '', $datas);
		$this->load->view('business/settle/view', $datas );

	}

	/**
	 * 기안별 기본 포맷 가져오기
	 * @return [type] [description]
	 */
	public function _settle_format() {
		$expense_type = $this->input->post( 'expense_type' );
		$settle_type = $this->input->post( 'settle_type' );
		$settle_no = $this->input->post( 'settle_no' );
		//$team_info = $this->Business_model->get_team_list_insert();

		$attendance_settle = $this->config->item('attendance_settle');
		// 기안 추가 정보
		if($settle_no>0) {
			$settle_info = $this->Business_model->get_settle(array('no'=>$settle_no), 'reg_date', '' ,'row');
			$settle_expense = $this->Business_model->get_settle_expense(array('s.settle_no'=>$settle_no, 's.expense_type'=>$expense_type));
			$reg_date = $settle_info['reg_date'];

			if(count($settle_expense) < 1){
				$settle_expense = array(
					array(
						'no'=>'',
						'settle_no'=>''
					)
				);

				$reg_date = date('Y-m-d');
			}
		}
		else {
			$settle_expense = array(
				array(
					'no'=>'',
					'settle_no'=>''
				)
			);

			$reg_date = date('Y-m-d');
		}


		$datas = array(
			'cfg'=>array(
				'expense_type'=>$expense_type
			),
			'settle_no'=>$settle_no,
			'settle_expense'=>$settle_expense,
		);

		$this->load->view('business/settle/format/'.$settle_type, $datas);
	}

	public function _settle() {
		$mode = $this->segs[3];

		switch ($mode) {
			case 'settle_input':
				$this->_settle_input();
			break;

			default:
				$this->_settle_list();
			break;
		}

	}

	function _settle_list(){
		$mode = $this->segs[3];
		$aSettleType = $this->aSettleType;

		$settle_tmp_list = $this->Business_model->get_settle_tmp_list();
		$dept_info = $this->Business_model->get_dept_list();
		$team_info = $this->Business_model->get_team_list();
		$cfg = array(
			'aSettleType'=>$aSettleType,
			'aSettleStatus'=>$this->aSettleStatus,
			'mode'=> $mode,
			'biz_info' => $this->Business_model->get_biz_list(),
			'page_info'=>$this->segs[4],
		);
		$datum = array(
			'cfg'=>$cfg,
			'settle_tmp_list'=> $settle_tmp_list,
			'dept_info'=> $dept_info,
			'team_info'=> $team_info,

		);

		$this->_render('settle/list', $datum);
	}

	public function _settle_paging_lists(){
		$p = $this->param;

		parse_str($this->input->post('search'), $assoc);
		$this->session->set_userdata('search',array_filter($assoc)); //검색데이터 세션처리

		$_SESSION['search'] = $assoc;
		// pre($assoc);

		$page = $this->input->post('page');//$p['page'];
		//$page = 1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;



		//검색조건설정

		$where_offset = array(
			's.tmp_yn'=>$assoc['tmp_yn']
		);

		/*
		$where = array(
			's.status'=>0,
			's.settle_type'=>1,
			's.user_no'=>$this->session->userdata( 'ss_user_no' )
		);
		*/
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'srch_settle_biz':
					$where["s.biz_id"]=$v;
				break;
				//case 'srch_settle_status':
				//	$where["s.status"]=$v;
				//break;
				case 'srch_settle_type':
					$where["s.settle_type"]=$v;
				break;
				case 'srch_start_date':
					$where["s.reg_date>='".$v."'"]=NULL;
				break;
				case 'srch_end_date':
					$where["s.reg_date<='".$v."'"]=NULL;
				break;
				case 'srch_title':
					//$where["s.title="]=$v;
					//$where["( s.title LIKE '%{$v}%' OR u.name LIKE '%{$v}%' )"]=NULL;
					$where["(s.title LIKE '%{$v}%' OR u.name LIKE '%{$v}%' OR s.content LIKE '%{$v}%')"]=NULL;
				break;
				case 'page_info':
					$page_info = $v;
				break;
				default :
					//$where[$k] = $v;
				break;
			}
		}

		//if($page_info != "A"){
			//$where['s.user_no']=$this->session->userdata( 'ss_user_no' );
		//}

		switch($page_info) {
			case 'W':		// 대기 : 다른사람이 작성 && 내가 결재 && 대기상태(내가 결재 해야 할 목록)
				$where['(p.user_no='.$this->session->userdata( 'ss_user_no' ).' and p.person_type=0 and p.status=0)']=NULL;
			break;
			case 'I':		// 진행 : ((내가 작성 && 다른사람이 결재) or (다른사람이 작성 && 내가 결재)) ???
				$where['((s.user_no='.$this->session->userdata( 'ss_user_no').' and s.status=2) or (p.user_no='.$this->session->userdata( 'ss_user_no' ).' and p.person_type=0) )']=NULL;
			break;
			case 'C':		// 완료 - 승인 : ((내가 작성 && 결재 완료) or (남이 작성 && 내가 결재완료))
				$where['(s.status=2 and s.user_no='.$this->session->userdata( 'ss_user_no' ).' or (p.user_no='.$this->session->userdata( 'ss_user_no' ).' and p.person_type=0 and p.status=2))']=NULL;
			break;
			case 'R':		// 반려 : (내가 작성 && 남이 반려, 남이 작성 - 반려
				$where['(s.status=3 and s.user_no='.$this->session->userdata( 'ss_user_no' ).' or (p.user_no='.$this->session->userdata( 'ss_user_no' ).' and p.person_type=0 and p.status=3))']=NULL;
			break;
			case 'F':		// 참조 : 나를 참조로 추가한 경우
				$where["p.person_type"]='1';
				$where['(s.user_no='.$this->session->userdata( 'ss_user_no' ).' or (p.user_no='.$this->session->userdata( 'ss_user_no' ).' and p.person_type=1))']=NULL;
			break;
			case '':		// 결재요청 : 내가 작성, 대기상태
				$where["s.status"]='0';
				$where['s.user_no']=$this->session->userdata( 'ss_user_no' );
			break;
			default :
				//$where[$k] = $v;
			break;
		}



		$team_list = $this->User_model->get_team_list();
		$dept_list = $this->User_model->get_dept_list();
		$biz_info = $this->Business_model->get_biz_list();
		$settle_type = $this->aSettleType;
		$settle_status = $this->aSettleStatus;

		//$rs = $this->Business_model->select_bizlog_paging($where, $offset, $limit, $where_offset);
		$rs = $this->Business_model->select_settle_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				//$row['reg_date'] = date('Y-m-d', strtotime($row['reg_date']));
				//$row['team_name'] = $team_list[$row['team_code']];
				//$row['dept_name'] = $dept_list[$row['dept_code']];
				//$row['status_txt'] = $this->work_status[$row['status']];
				$row['biz_name'] = $biz_info[$row['biz_id']]['biz_name'];
				$row['settle_type_name'] = $settle_type[$row['settle_type']];
				$row['status_txt'] = $settle_status[$row['status']];

				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		// pre($list);

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'settle_status'=>$settle_status
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}



	// 20170119 kruddo : 전자결재 임시저장 기능 추가 - 임시저장 목록
	/*
	function settle_tmp_list_paging() {
		$p = $this->param;

		parse_str($this->input->post('search'), $assoc);
		$this->session->set_userdata('search',array_filter($assoc)); //검색데이터 세션처리

		$_SESSION['search'] = $assoc;
		// pre($assoc);

		$page = $assoc['page'];
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;


		//검색조건설정
		$where = array();
		$where_offset = array(
			'tmp_yn'=>$assoc['tmp_yn']
		);


		$team_list = $this->User_model->get_team_list();
		$dept_list = $this->User_model->get_dept_list();

		$rs = $this->Business_model->select_bizlog_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['reg_date'] = date('Y-m-d', strtotime($row['reg_date']));
				$row['team_name'] = $team_list[$row['team_code']];
				$row['dept_name'] = $dept_list[$row['dept_code']];
				$row['status_txt'] = $this->work_status[$row['status']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		// pre($list);

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


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
	*/

	// 20170119 kruddo : 전자결재 임시저장 기능 추가 - 임시 저장 목록



	// 기안 작성
	public function _settle_input() {
		$this->page_title = "신규등록";

		$this->load->helper('array');

		$settle_num_title = $this->session->userdata( 'ss_team_name' )."-".date('Ymd');
		$settle_num_cnt = $this->Business_model->get_settle_num_cnt( $settle_num_title );
		$settle_num_cnt = sprintf("%03d", $settle_num_cnt+1);

		$advice_person = $this->Business_model->get_advice_person();
		//$biz_info = $this->Business_model->get_biz_list();
		$biz_info = $this->Business_model->get_biz_list();
		$settle_tmp_list = $this->Business_model->get_settle_tmp_list();
		$team_info = $this->Business_model->get_team_list_insert();

		//print_r($team_info); exit;
		$aDatas = array (
				'aSettleType'=>$this->aSettleType,
				'settle_expense_type'=>$this->aSettleExpenseType,
				'settle_expense_devision'=>$this->aSettleExpenseDevision,
				'settle_expense_holiday'=>$this->aSettleExpenseHoliday,
				'settle_expense_route' => $this->aSettleExpenseRoute,
				'settle_expense_doctor' => $this->aSettleExpenseDoctor,
				'view_data'=>array(
					'reg_date'=>date('Y-m-d H:i:s'),
					'name'=>$this->session->userdata( 'ss_name' ),
					'settle_number'=>$settle_num_title."-".$settle_num_cnt
				),
				'user_no'=>$this->session->userdata( 'ss_user_no' ),

				'advice_person'=>$advice_person,
				'biz_info'=>$biz_info,
				'settle_tmp_list'=>$settle_tmp_list,
				'team_info'=>$team_info,
				'biz_id'=>$this->session->userdata( 'ss_biz_id' ),
		);

		//$this->func_lib->pr( $aDatas ); exit;
		$this->load->view('business/settle/input', $aDatas );
	}

	// 기안 작성
	public function _settle_modify() {
		// $mode = $this->segs[];
		$settle_no = $this->segs[3];

		$this->load->helper('array');

		$this->page_title = "수정";

		$view_data = $this->Business_model->get_settle_view( $settle_no );
		$view_data['settle_type_str'] = $this->aSettleType[$view_data['settle_type']];

		$settle_draft = $this->Business_model->get_settle_draft( $settle_no );

		// 결재 라인
		$approval = $this->Business_model->get_settle_person( $settle_no, 0 );

		$settle_status_chk = true;
		foreach( $approval as $row ){
			//$this->func_lib->pr( $row );
			if( $row['status'] != 0 ){
				$settle_status_chk = false;
				break;
			}
		}

		// 참조
		$refer = $this->Business_model->get_settle_person( $settle_no, 1 );

		$this->_check_auth($view_data, $approval, $refer);

		// 파일
		$settle_file = $this->Business_model->get_settle_file( $settle_no );

		// 상담 실장
		$advice_person = $this->Business_model->get_advice_person();

		// 참고 내용
		$settle_consult = $this->Business_model->get_settle_consult($settle_no);
		//$biz_info = $this->Business_model->get_biz_list();
		$biz_info = $this->Business_model->get_biz_list();
		$settle_tmp_list = $this->Business_model->get_settle_tmp_list();
		$team_info = $this->Business_model->get_team_list( 90 );

		$view_data['approval_person'] = implode(',',(array_column($approval,'user_no')));
		$view_data['refer_person'] = implode(',',(array_column($refer,'user_no')));

		$mode = 'modify';
		$datas = array(
				'view_data' => $view_data,
				'approval' => $approval,
				'refer' => $refer,
				'mode'=>$mode,
				'aSettleType'=>$this->aSettleType,
				'settle_status' => $this->aSettleStatus,
				'settle_expense_route' => $this->aSettleExpenseRoute,
				'settle_expense_doctor' => $this->aSettleExpenseDoctor,
				'settle_expense_holiday' => $this->aSettleExpenseHoliday,
				'settle_expense_type' => $this->aSettleExpenseType,
				'settle_expense_devision' => $this->aSettleExpenseDevision,
				'advice_person' => $advice_person,
				'settle_file' => $settle_file,
				'settle_expense' => $settle_expense,
				'settle_consult' => $settle_consult,
				'settle_draft' => $settle_draft,
				'biz_info' => $biz_info,
				'settle_tmp_list' => $settle_tmp_list,
				'team_info'=>$team_info,
				'mode' => $mode,
				'settle_status_chk' => $settle_status_chk
		);

		// pre($datas);exit;

		$this->load->view('business/settle/input', $datas );
	}

	// 결재라인
	public function _settle_line() {

		// 사업장 정보
		//$biz_info = $this->Business_model->get_biz_list();
		$biz_info = $this->session->userdata( 'ss_my_biz_list' );
		$save_line = $this->Business_model->get_save_line_list();
		$datas = array (
				'biz_info'=>$biz_info,
				'save_line'=>$save_line,
		);

		$this->load->view('business/settle/line', $datas);
	}

	// 20170119 kruddo : 결재 임시 저장 목록
	public function _settle_tmp_list() {

		$this->load->view('business/settle/settle_tmp_list', $datas);
	}
	// 20170119 kruddo : 결재 임시 저장 목록

	/**
	 * 근태신청서 타입별 코드
	 * @return [type] [description]
	 */
	public function get_settle_holiday(){
		$type = $this->input->post('type');
		$attendance_settle = $this->config->item('attendance_settle');
		$attendance = $this->config->item('attendance');
		$holiday = array();
		foreach($attendance_settle[$type]['attendance'] as $code) {
			$holiday[$code] = $attendance[$code];
		}

		if(empty($holiday)) {
			return_json(false);
		}
		else {
			return_json(true, '', $holiday);
		}


	}

	// 저장된 결재라인
	public function get_save_line_person() {

		$what = $this->segs[3];
		$code = $this->segs[4];

		$list = $this->Business_model->get_user_info_list( $what, $code );
		echo json_encode( $list );
	}

	// 저장된 결재라인
	public function _save_line() {
		// 사업장 정보
		$biz_info = $this->Business_model->get_biz_list();
		$save_line = $this->Business_model->get_save_line_list();

		$datas = array (
				'biz_info'=>$biz_info,
				'save_line'=>$save_line,
		);

		$this->load->view('business/settle/save_line', $datas);
	}

	public function get_team_json() {
		$dept_code = $this->segs[3];
		$list = $this->Business_model->get_team_list( $dept_code );
		echo json_encode( $list );
	}

	public function get_dept_json() {
		$hst_code = $this->segs[3];
		$biz_id = $this->segs[4];
		$list = $this->Business_model->get_dept_list( $hst_code, $biz_id );
		echo json_encode( $list );
	}

	public function get_user_info_json() {
		$what = $this->input->post( 'what' );
		$code = $this->input->post( 'code' );

		$list = $this->Business_model->get_user_info_list( $what, $code );
		//print_r($list);
		$data = array();
		foreach( $list as $key => $val ){
			$data['_'.$key]	= $val;
		}
		echo json_encode( $data );
	}

	// 기안참조
	public function _draft_refer() {
		$mode = isset($_GET['draft_mode']) ? $_GET['draft_mode'] : "";

		$datas = array (
				'draft_mode'=>$draft_mode
		);

		$this->page_title = "기안선택";
		$this->load->view('business/settle/draft');
	}

	// 기안 참조 리스트
	public function _draft_refer_list() {

		$page = isset($_GET['page']) ? $_GET['page'] : 1;
		$limit = isset($_GET['rows']) ? $_GET['rows'] : 10;
		$sidx = isset($_GET['sidx']) ? $_GET['sidx'] : "no";
		$sord = isset($_GET['sord']) ? $_GET['sord'] : "desc";

		$first = ($page - 1) * $limit;
		$where = "where s.stauts = 2 ";

		if ( $_GET['srch_settle_status'] != "" ){
			if( $where ){
				$where .= " AND ";
			}

			$where .= " s.status = ".$_GET['srch_settle_status'];
		}

		/*
		if( $where ){
			$where = "WHERE ".$where;
		}
		*/

		$order_by = " ORDER BY ".$sidx." ".$sord;

		$result = $this->Business_model->get_settle_list( $first, $limit, $where, $order_by );
		$total = $this->Business_model->get_settle_total( $where );

		foreach ( $result as $i => $row ) {
			$no = $total - $first - $i;

			$list->rows[$i]['id'] = $row->no;
			$list->rows[$i]['cell'] = array (
					//$no,
					$row->no,
					$row->settle_number,
					$this->aSettleType[$row->settle_type],
					$row->reg_date,
					$row->name,
					$row->title,
			);
		}

		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		echo json_encode( $list );
	}

	// 파일 다운로드
	function download_file()
	{
		$this->load->helper('download');
		$file_name = $this->input->get( 'file_name' );
		$ori_name = $this->Business_model->get_ori_name( $file_name );
		$data = file_get_contents("./images/settle/".$file_name);

		// 한글 파일명 IE에서 깨짐 처리
		force_download(mb_convert_encoding($ori_name, 'euc-kr', 'utf-8'), $data);
	}



	private function _check_auth($data, $approval, $refer) {
		$is_valid = false;

		if ($data['user_no'] == $this->session->userdata( 'ss_user_no' )) {
			$is_valid = true;
		}

		foreach( $approval as $row ){
			if( $row['user_no'] == $this->session->userdata( 'ss_user_no' )) {
				$is_valid = true;
				break;
			}
		}

		foreach( $refer as $row ){
			if( $row['user_no'] == $this->session->userdata( 'ss_user_no' )) {
				$is_valid = true;
				break;
			}
		}

		//pre($this->session->userdata);
		//exit;

		if ($is_valid === false) {
			alert( '정상적인 경로가 아닙니다.#1' );
		}

	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "business/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}

}



