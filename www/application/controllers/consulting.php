<?php
/**
 * 작성 : 2014.10.27
 * 수정 : 2015.01.27
 * 수정 : 2015.02.25 -DB 규칙 개정 관련
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Consulting extends CI_Controller {
	var $charge_user_id = '';
	var $contact_team_name = '';
	var $contact_team_code = '';

	public function __construct() {
		parent::__construct();
		session_start();

		$this->load->model( array (
			'User_model',
			'Consulting_model',
			'Manage_model'
		) );

		$this->load->library( array (
			'consulting_lib'
		) );

		$this->load->helper('cookie');
		$this->yield = TRUE;

		$this->path_list = $this->config->item( 'all_path' );
		$this->cst_status_list = $this->config->item( 'cst_status' );
		$this->sex_list = $this->config->item( 'sex' );

		$this->limit_date = date( 'YmdHis', mktime( date( 'H' ), date( 'i' ) - 30, 0, date( 'm' ), date( 'd' ), date( 'Y' ) ) );

		$this->param = $this->input->post(NULL, true);
	}


	private function _set_page_title($type) {
		$this->page_title = (empty( $type )) ? "팀 DB" : "공동 DB";
	}

	public function main($type) {

		if (!in_array($type, array('','share','99','cpa'))) {
			alert( '정상적인 경로가 아닙니다.' );
		}

		$main_category = $this->Manage_model->get_code_item( '01' );
		$team_list = $this->User_model->get_team_list( '90' );

		//영구DB(MYDB) 전체 건수
		$where = array("permanent_status='Y'");
		$cnt_mydb = $this->Consulting_model->get_cst_total($where);

		//이번달 MYDB 건수
		$where = array(
			"permanent_status='Y'",
			"reg_date >= '".date('Ym01000000')."'"
		);

		$cnt_mydb_month = $this->Consulting_model->get_cst_total($where);

		//공동DB에 있는 MYDB건수
		$where = array("permanent_status='Y'");
		$cnt_sharedb = $this->Consulting_model->get_cst_total($where,'share');

		//이번달 공동DB에 있는 MYDB건수
		$where = array(
			"permanent_status='Y'",
			"reg_date >= '".date('Ym01His')."'"
		);
		$cnt_sharedb_month = $this->Consulting_model->get_cst_total($where,'share');

		$page = 1;
		$search = $_SESSION['search'];

		//내원담당자 정보
		if ($this->session->userdata( 'ss_dept_code' ) == '90' && $type != 'share') {
			$charger_list = $this->common_lib->search_user(array('dept_code'=>'90', 'status'=>'1'), 'no, user_id, name');
			$charger_list['stonek700'] = array('no'=>'1','user_id'=>'stonek700', 'name'=>'김석');
			//pre($charger_list);
			//$charger_list = $this->User_model->get_team_user($this->session->userdata( 'ss_team_code' ));
		}
		else {
			$biz_team = $this->User_model->get_team(array('biz_id'=>$this->session->userdata('ss_biz_id')));
			if(!empty($biz_team)) {
				$rs = $this->User_model->get_user_all(array('team_code'=>array_keys($biz_team), 'dept_code'=>"90", 'status'=>'1', 'team_code !='=>'98'));
				foreach($rs as $id => $row) {
					$charger_list[$id]=array('user_id'=>$id, 'name'=>$row['name']);
				}
				$charger_list['stonek700'] = array('no'=>'1','user_id'=>'stonek700', 'name'=>'김석');
			}
			else $charger_list = array();
		}

		$rs = $this->User_model->get_user_all(array('team_code'=>array_keys($biz_team), 'dept_code'=>"90", 'status'=>'1', 'team_code !='=>'98'));
		foreach($rs as $id => $row) {
					$charger_list[$id]=array('user_id'=>$id, 'name'=>$row['name']);
		}
		$charger_list['stonek700'] = array('no'=>'1','user_id'=>'stonek700', 'name'=>'김석');




		//상담팀장 + 김석
		$closing_list = $this->common_lib->search_user(array('dept_code'=>'90', 'status'=>'1', 'duty_code'=>'7'), 'no, user_id, name');
		$closing_list['stonek700'] = array('no'=>'1','user_id'=>'stonek700', 'name'=>'김석');


		// 유입경로 중국
		$path_list_value = $this->path_list ;
		if ($this->session->userdata( 'ss_biz_id' ) != 'ezham_cn') {
			unset($path_list_value['A']);
		}

		$biz = $this->session->userdata('ss_my_biz_list');
		unset($biz[$this->session->userdata('ss_biz_id')]);

		$biz_group = $this->common_lib->get_biz_group();
		$crm_status = $this->Manage_model->get_code_item('07', '', 'all',array('biz_id'=>$biz_group) );//예약상태


		$data = array(
			'type'=>$type,
			// 'team_list'=>$team_list,
			// 'main_category'=>$main_category,
			'count'=>array(
				'mydb'=>$cnt_mydb,
				'mydb_month'=>$cnt_mydb_month,
				'sharedb'=>$cnt_sharedb,
				'sharedb_month'=>$cnt_sharedb_month
			),
			'cfg'=>array(
				'path'=>$path_list_value,
				'status'=>$this->cst_status_list,
				'category'=>$main_category,
				'team'=>$team_list,
				'charger'=>$charger_list,
				'closing'=>$closing_list,
				'biz'=>$biz,
				'crm_status'=>$crm_status
			),
			'auth'=>array(
				'm_check'=>$this->common_lib->check_auth_group('m_check'),
				'db_share'=>$this->common_lib->check_auth_group('db_share'),
				'db_input'=>$this->common_lib->check_auth_group('db_input'),
				'sms_limit'=>$this->common_lib->check_auth_group('sms_limit'),
				'bizid_transfer'=>$this->common_lib->check_auth_group('bizid_transfer'),
				'mydb'=>($this->session->userdata('ss_dept_code')=='90')?true:false,
				'data_download'=>$this->common_lib->check_auth_group('data_download'),
				'ex_path'=>$this->common_lib->check_auth_group('ex_path'),
				'ex_chargedate'=>$this->common_lib->check_auth_group('ex_chargedate'),
				'cst_charger'=>$this->common_lib->check_auth_group('cst_charger')
			),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:$page,

			// 'path_list_value'=>$path_list_value,
			'charger_list'=>$charger_list
		);

		$this->_render('index',$data);
		// $this->load->view( 'consulting/index', $data);
	}


	function consulting_list_paging($assoc='') {
		$p = $this->param;

		$this->load->library('patient_lib');
		$this->load->model('patient_model');

		if(empty($assoc)) {
			parse_str($this->input->post('search'), $assoc);
		}

		$_SESSION['search'] = array_filter($assoc); //검색데이터 세션처리

		$type = $p['type']; //share:공동DB, 99:재진관리, cpa:CPA내역
		$page = ($assoc['page'])?$assoc['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		$where_offset = array(
			'c.use_flag'=>'Y',
			'c.hst_code'=>$this->session->userdata('ss_hst_code'),
			'c.is_x'=>'N'
			//'c.biz_id'=>$assoc['grant_biz']
		);

		if($type == 'share') {
			$where_offset['c.cst_status']= '00';
			//$where_offset['c.charge_date < ']=date('YmdHis');
		}
		else if($type=='99') {
			if ($this->session->userdata( 'ss_dept_code' ) == '90') {
				$where_offset['c.team_code'] = $this->session->userdata('ss_team_code');
				$order_by = "accept_flag DESC, cst_seqno DESC";
			}
			$where_offset['c.charge_date >= ']=date('YmdHis');
			$where_offset['c.cst_status']='99';
		}
		else if($type == 'cpa') {
			$where_offset['c.cpa !=']='wait';
		}
		else {
			//7팀은 유입경로 - 기존고객 & 7팀DB 확인가능
			if ($this->session->userdata('ss_team_code') == '98') {
				$where_offset['(c.team_code="98" OR c.path="E")'] = NULL;
			}
			/*
			if ($this->session->userdata( 'ss_dept_code' ) == '90') {
				$where_offset['c.team_code'] = $this->session->userdata('ss_team_code');

			}
			*/

			if ($this->session->userdata( 'ss_dept_code' ) == '90') {
				$order_by = "c.reg_date DESC";
			}
			$where_offset['c.cst_status !=']= '00';
		}

		if(DEV === true) {
			//pre($where_offset);
		}


		$where = array();
		if($assoc['treat_cost_no']) {
			$this->load->model('treat_model');

			$treat_row = $this->treat_model->select_cost_row(array('no'=>$assoc['treat_cost_no']));
			//pre($treat_row);
		}

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('type','limit', 'page', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( c.name LIKE '%{$v}%' OR c.tel LIKE '%{$v}' OR c.messenger LIKE '%{$v}%' )"]=NULL;
				break;
				case 'oper_date':
					$where["c.oper_date LIKE"] = "%{$v}%";
				break;
				case 'reg_date_s':
					$where['c.reg_date >=']=str_replace('-','',$v)."000000";
				break;
				case 'reg_date_e':
					$where['c.reg_date <=']=str_replace('-','',$v)."235959";
				break;
				case 'charge_date_s':
					$where['c.charge_date >=']=str_replace('-','',$v)."000000";
				break;
				case 'charge_date_e':
					$where['c.charge_date <=']=str_replace('-','',$v)."999999";
				break;
				case 'sales_min':
					$where['c.sales >=']=$v;
				break;
				case 'sales_max':
					$where['c.sales <=']=$v;
				break;
				case 'media':
					$where['c.'.$k] = $v;
				break;
				case 'is_o':
				case 'grade_type':
					$where['p.'.$k] = $v;
				break;
				case 'treat_cost_no':
					$where["CONCAT('_',c.treat_cost_route,'_') LIKE '%\_{$v}\_%'"] = NULL;
				break;
				case 'favorite':
					if($v == 'N') continue;
					$user_no = $this->session->userdata("ss_user_no");
					$where["CONCAT(',',c.favorite_user,',') LIKE '%,{$user_no},%'"] = NULL;
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
		//pre($assoc);
		//pre($where);

		$team_list = $this->User_model->get_team_list( '90' );
		$main_category = $this->Manage_model->get_code_item( '01' );
		$sub_category = $this->Manage_model->get_code_item( '02' );
		$user_list = $this->User_model->get_team_user();


		$rs = $this->Consulting_model->select_consulting_paging($where, $offset, $limit, $where_offset, $order_by);
		if($rs['count']['search'] > 0) {

			$appointment_status = $this->common_lib->get_cfg('appointment_status'); //예약상태
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				//CRM상태
				$crm = $this->patient_model->select_patient_join(array('p.cst_seqno'=>$row['cst_seqno'], 'p.is_delete'=>'N'),'patient_appointment','sub.*','sub.appointment_date DESC');
				$crm_row = array_shift($crm);
				$row['crm_status'] = $crm_row['status_code'];
				$row['crm_status_txt'] = $appointment_status[$crm_row['status_code']];

				//즐겨찾기
				$favorite_arr = explode(',',$row['favorite_user']);
				$row['is_favorite'] = (in_array($this->session->userdata('ss_user_no'), $favorite_arr))?'Y':'N';


				//유효기간
				if ($type == '') {
					$charge_date = ($row['charge_date'] == '99999999999999')?'-' : set_date_format('Y-m-d',$row['charge_date']);
				}

				$patient_info = $this->patient_model->select_patient_row(array('cst_seqno'=>$row['no']));
				$row['is_o'] = $patient_info['is_o'];



				//최종상담
				if($row['mod_date']) {
					$row['mod_date'] = set_date_format('m-d H:i', $row['mod_date']); //최종수정일
				}

				//메모
				$memo = $this->Consulting_model->select_consulting(array('cst_seqno'=>$row['cst_seqno']), 'seqno, memo', 'seqno DESC','', 'consulting_memo');
				if(empty($memo)) $memo = array();

				$memo_summary = htmlspecialchars($memo[0]['memo'], ENT_QUOTES);

				//db보기권한 -  팀DB에서 공동DB로 최초 이동된후 +7일간 초기분배팀에 보기권한없음 by 이혜진 20150511
				if($type == 'share') {
					$grant_view = $this->consulting_lib->check_grant_share($row['cst_seqno'], $row['charge_date']);
				}
				else {
					$grant_view = 'Y';
				}

				//전화번호
				$tel = tel_check(trim($row['tel']), '-' );

				if($this->output=='excel') {
					//$tel = set_blind( 'phone', $tel);
				}
				else {
				}


				$tel_org = $row['tel'];
				if($type == 'share') {
					$tel = $this->common_lib->manufacture_mobile($tel,'','ex_phone2');
				}
				else {
					$tel = $this->common_lib->manufacture_mobile($tel, $row['team_code'],'ex_phone2');
				}



				$birth = (substr($row['birth'],0,1) >= 3)?'19'.$row['birth']:$row['birth'];
				$age = date('Y')-substr($birth,0,4)+1;

				//중복데이터(전화번호)수(재문의횟수)
				if($row['tel']){
					$duplicate_db = $this->Consulting_model->count_db(array('db_status'=>'8','tel'=>$row['tel']));
				}
				else {
					$duplicate_db = 0;
				}

				$db_info = $this->Consulting_model->select_db_row(array('db_seqno'=>$row['db_seqno']), 'reg_date');
				$row['reg_date'] = $db_info['reg_date'];

				$row['duplicate_db'] = $duplicate_db;

				$row['age'] = $age;
				$row['birth'] = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3", $birth);
				$row['path_txt'] = $this->path_list[$row['path']];
				$row['reg_date'] = set_date_format('Y-m-d H:i', $row['reg_date']); //접수일자
				$row['main_category'] = $main_category[$row['main_category']]; //시술항목
				$row['sub_category'] = $sub_category[$row['sub_category']]; //시술부위
				$row['manager_team'] = $team_list[$row['team_code']]; //팀
				$row['manager'] = $user_list[$row['charge_user_id']]; //담당자
				$row['manager_closing'] = $user_list[$row['closing_user_id']]; //담당자
				$row['status'] = $this->cst_status_list[$row['cst_status']]; //상태
				$row['sales'] = number_format($row['sales']);
				$row['treat_nav'] = ($row['treat_cost_no'])?$this->patient_lib->set_treat($row['treat_cost_no'], 'text', 'short'):'';
				$row['treat_nav_long'] = ($row['treat_cost_no'])?$this->patient_lib->set_treat($row['treat_cost_no'], 'text', 'long'):'';
				// $row['media'] = $this->consulting_lib->trans_media($row['media']);
				$row['charge_date']=$charge_date;
				$row['grant_view']=$grant_view;
				$row['memo']=$memo;
				$row['memo_summary']=$memo_summary;
				$row['tel']=$tel;
				$row['tel_org']=$tel_org;
				//$row['patient'] = $this->patient_model->select_patient_row(array('cst_seqno'=>$row['cst_seqno']));

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
		$sum = $this->Consulting_model->select_consulting_row(array_merge($where, $where_offset), 'consulting_info AS c LEFT JOIN patient AS p ON (c.cst_seqno=p.cst_seqno)', $sum_field);


		$user_no = $this->session->userdata('ss_user_no');
		$rs['count']['favorite'] = $this->Consulting_model->consulting_count(array("CONCAT(',',favorite_user,',') LIKE '%,{$user_no},%'"=>NULL));


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

	/**
	 * 엑셀다운로드
	 * @return [type] [description]
	 */
	function excel() {
		$p = $this->input->get(NULL, true);
		$this->param['limit'] = $p['limit'];
		// $p['page']=1;
		// $this->param = $p;
		// pre($p);
		$this->output="excel";
		$list = $this->consulting_list_paging($p);

		$this->load->dbutil();
		// $this->load->helper('file');
		$this->load->helper('download');
		$filename = "팀DB.xls";
		$body = $this->layout_lib->fetch_('/consulting/excel.html', $list);
		force_download($filename, $body);
	}

	public function excel_lists($type) {
		$this->yield = FALSE;

		$list = $this->lists( $type, 'EXCEL' );

		$this->_set_page_title( $type );
		output_excel( iconv( 'utf-8', 'euckr', str_replace( ' ', '', $this->page_title ) . '_' . date( 'Ymd' ) ) );
		$this->load->view( 'consulting/excel', array (
				'list'=>$list
		) );
	}


	public function cpa($type) {

		$main_category = $this->Manage_model->get_code_item( '01' );
		$team_list = $this->User_model->get_team_list( '90' );


		$page = 1;
		$search = $_SESSION['search'];

		//담당자 정보
		if ($this->session->userdata( 'ss_dept_code' ) == '90' && $type != 'share') {
			$charger_list = $this->User_model->get_team_user($this->session->userdata( 'ss_team_code' ));
		}
		else {
			$biz_team = $this->User_model->get_team(array('biz_id'=>$this->session->userdata('ss_biz_id')));
			if(!empty($biz_team)) {
				$rs = $this->User_model->get_user_all(array('team_code'=>array_keys($biz_team), 'dept_code'=>"90", 'status'=>'1'));
				foreach($rs as $id => $row) {
					$charger_list[$id]=$row['name'];
				}
			}
			else $charger_list = array();
		}

		// 유입경로
		$path_list_value = $this->path_list ;
		$biz = $this->session->userdata('ss_my_biz_list');
		unset($biz[$this->session->userdata('ss_biz_id')]);

		$data = array(
			'type'=>$type,
			'cfg'=>array(
				'path'=>$path_list_value,
				'status'=>$this->cst_status_list,
				'category'=>$main_category,
				'team'=>$team_list,
				'charger'=>$charger_list,
				'biz'=>$biz
			),
			'auth'=>array(
				'm_check'=>$this->common_lib->check_auth_group('m_check'),
				'db_share'=>$this->common_lib->check_auth_group('db_share'),
				'db_input'=>$this->common_lib->check_auth_group('db_input'),
				'sms_limit'=>$this->common_lib->check_auth_group('sms_limit'),
				'bizid_transfer'=>$this->common_lib->check_auth_group('bizid_transfer'),
				'mydb'=>($this->session->userdata('ss_dept_code')=='90')?true:false,
				'data_download'=>$this->common_lib->check_auth_group('data_download'),
				'ex_path'=>$this->common_lib->check_auth_group('ex_path'),
				'ex_chargedate'=>$this->common_lib->check_auth_group('ex_chargedate')
			),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:$page,
			'charger_list'=>$charger_list
		);

		$this->_render('cpa',$data);
	}




	public function input($cst_seqno) {
		$this->load->library('patient_lib');

		$main_category = $this->Manage_model->get_code_item( '01' );
		$job_list = $this->Manage_model->get_code_item( '05' );
		$this->team_list = $this->User_model->get_team_list( '90' );

		$row = $this->Consulting_model->get_cst_info( $cst_seqno );


		//권한체크
		$type = $this->consulting_lib->check_type($row['charge_date']);

		if($type == 'share') {
			$grant_view = $this->consulting_lib->check_grant_share($row['cst_seqno'], $row['charge_date']);
		}
		else {
			$grant_view = 'Y';
		}

		if(empty($row) || (X && $row['is_x']=='Y')) $grant_view = 'N';

		if($grant_view=='N') {
			alert('이 데이터(DB)를 볼 수 있는 권한이 없거나 존재하지 않는 인덱스입니다.');
			exit;
		}

		// 접수일 업데이트
		if ($row['accept_date'] == '' && $row['team_code'] == $this->session->userdata( 'ss_team_code' )) {
			$input = null;
			$input['accept_date'] = TIME_YMDHIS;
			$this->Consulting_model->update_cst( $cst_seqno, $input );
		}

		$type = $this->consulting_lib->check_type($row['charge_date']);
		$this->page_title = ($type == 'share')? '공동 DB':'팀 DB';
		$select_date_valid = $this->consulting_lib->check_appointment_cnt($type, $row['appointment_cnt']);

		// 권한 체크
		$valid = true;

		//7팀은 기존고객 및 본인팀DB만 확인가능 by hjlee 20170516
		if ($this->session->userdata('ss_team_code') == '98' && ($row['path']!='E' && $row['team_code']!='98')) {
			$valid = false;
			alert( '정상적인 경로가 아닙니다.' );
		}
		/*
		if ($this->session->userdata('ss_dept_code') == '90') {
			$valid = $this->consulting_lib->have_auth( $type, $row );

			if (! $valid) {
				alert( '정상적인 경로가 아닙니다.' );
			}
		}
		*/

		//중복데이터(전화번호)수(재문의횟수)
		$duplicate_db = $this->Consulting_model->count_db(array('db_status'=>'8','tel'=>$row['tel']));
		$row['duplicate_db'] = $duplicate_db;

		//진료항목
		$row['treat_nav'] = $this->patient_lib->set_treat($row['treat_cost_no'], 'text');
		//관심항목
		$row['treat_info'] = $this->patient_lib->set_treat($row['treat_cost_no_interest']);

		//db등록자
		$db_infos = array_shift($this->Consulting_model->get_db_in($row['db_seqno']));
		if($db_infos['reg_user_id']){
			$reg_user = $this->User_model->get_info(array('user_id'=>$db_infos['reg_user_id']),'name');
			$row['reg_user_name'] = $reg_user['name'];
		}
		else {
			$row['reg_user_name'] = '자동등록';
		}


		//최초팀의 경우 공동DB데이터를 만료후 7일간 볼수 없음 TODO
		$row['birth'] = ($row['birth'] == 0) ? '' : $row['birth'];
		$row['path_txt'] = $this->path_list[$row['path']];
		$row['reg_date_txt'] = set_long_date_format( '-', $row['reg_date'] );
		$row['sales'] = set_blank($row['sales']);

		if ($this->consulting_lib->team_name != '') {
			$row['team_code'] = $this->consulting_lib->contact_team_code;
			$row['team_name'] = $this->consulting_lib->contact_team_name;
			$row['charge_user_id'] = $this->consulting_lib->contact__user_id;
		} else {
			$row['team_name'] = $this->team_list[$row['team_code']];
		}

		//환자등록여부
		$this->load->model('Patient_Model');
		$patient = $this->Patient_Model->select_patient_row(array('cst_seqno'=>$cst_seqno));
		if($patient) {
			//총매출액
			$amount = $this->Patient_Model->select_patient_row(array('patient_no'=>$patient['no'], 'is_delete'=>'N'), 'patient_pay', "SUM(IF(sales_type=3,amount_paid,0)) AS deposit, SUM(IF(pay_type='refund',amount_refund, 0)) AS refund");
			$patient['deposit'] = $amount['deposit'];
			$patient['refund'] = $amount['refund'];
			$row['patient'] = $patient;
		}
		else {
			$row['patient'] = null;
		}

		//$row['tel'] = ($this->_valid_view_tel())? tel_check( $row['tel'], '-' ):set_blind( 'phone', tel_check( $row['tel'], '-' ) );
		$row['tel'] = $this->common_lib->manufacture_mobile($row['tel'], $row['team_code'],'ex_phone2');

		if ($type == 'share') {
			if ($this->session->userdata( 'ss_dept_code' ) == '90') {
				$contact_row = $this->Consulting_model->get_contact_info( $cst_seqno );
				$row['contact_date'] = dead_line( $contact_row['contact_date'] );
				$row['contact_seqno'] = $contact_row['seqno'];
			}
		}


		$result = $this->Consulting_model->get_memo_list( $cst_seqno );
		foreach ( $result as $i => $memo_row ) {
			$memo_list[$i]['memo'] = $memo_row['memo'];
			$memo_list[$i]['reg_date'] = set_long_date_format( '-', $memo_row['reg_date'] );
			$memo_list[$i]['reg_user_id'] = $memo_row['reg_user_id'];
			$memo_list[$i]['name'] = $memo_row['name'];
		}

		//저장권한
		$save_valid = false;
		$result = $this->Manage_model->get_access_list(2);
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

		if($row['cst_status'] == '09') { //사업장변경상태시 저장불가
			// $save_valid = false;
		}

		//이전글, 다음글
		if ($type == '') {
			$neighbor['next'] =$this->_get_neighbor($cst_seqno, 'PREV');
			$neighbor['prev'] =$this->_get_neighbor($cst_seqno, 'NEXT');
		}


		//유효기간 변경 권한

		// 20170207 kruddo : 고객정보 > 상담일지 갯수 추가(상담갯수 0일때 상담일지 안 보이도록
		$tbl = 'consulting';
		$cnt[$tbl] = $this->Patient_Model->count_patient(array('patient_no'=>$patient['no']), "patient_{$tbl}");
		// 20170207 kruddo : 고객정보 > 상담일지 갯수 추가(상담갯수 0일때 상담일지 안 보이도록

		//상담실장전체 + 김석
		$user_charger = $this->common_lib->search_user(array('dept_code'=>'90', 'status'=>'1'), 'no, user_id, name');
		$user_charger['stonek700'] = array('no'=>'1','user_id'=>'stonek700', 'name'=>'김석');

		//상담팀장 + 김석
		$user_closing = $this->common_lib->search_user(array('dept_code'=>'90', 'status'=>'1', 'duty_code'=>'7'), 'no, user_id, name');
		$user_closing['stonek700'] = array('no'=>'1','user_id'=>'stonek700', 'name'=>'김석');


		$data = array (
			'row'=>$row,
			'main_category'=>$main_category,
			'job_list'=>$job_list,
			'cst_status_list'=>$this->cst_status_list,
			'team_list'=>$this->team_list,
			'memo_list'=>$memo_list,
			'type'=>$type,
			'save_valid'=>$save_valid,
			'select_date_valid'=>$select_date_valid,
			'neighbor'=>$neighbor,
			'path'=>$this->path_list,
			'consulting_cnt'=>$cnt[$tbl],				// 20170207 kruddo : 고객정보 > 상담일지 갯수 추가(상담갯수 0일때 상담일지 안 보이도록
			'auth'=>array(
				'm_chargedate'=>$this->common_lib->check_auth_group('m_chargedate'),
				'path_change'=>$this->common_lib->check_auth_group('path_change'),
				'ex_phone2'=>$this->common_lib->check_auth_group('ex_phone2')
			),
			'user'=>array(
				'charger'=>$user_charger, //상담실장
				'closing'=>$user_closing //상담팀장
			)
		);


		$this->_render('input', $data);
		// $this->load->view( 'consulting/input', $data );
	}


	function tab_log() {
		$cst_seqno = $this->param['cst_seqno'];
		$rs = $this->Consulting_model->get_log_list( $cst_seqno );
		$list = array();
		$team_list = $this->User_model->get_team_list('','all','all');
		$idx = count($rs);
		foreach($rs as $row) {
			$row['reg_date'] = date('Y-m-d H:i:s', strtotime($row['reg_date']));
			$row['team_name'] = $team_list[$row['team_code']];
			$row['idx'] = $idx--;
			$list[] = $row;
		}
		// pre($list);
		$datum = array(
			'list'=>$list
		);
		$this->_render('tab_log', $datum, 'inc');
	}

	function tab_contact() {
		$cst_seqno = $this->param['cst_seqno'];
		$team_list = $this->User_model->get_team_list('','all');

		$where['cst_seqno'] = $cst_seqno;
		$rs = $this->Consulting_model->get_contact_list( '*', $where );
		$idx = count( $rs );
		foreach ( $rs as $row ) {
			$row['idx'] = $idx--;
			$row['reg_date'] = date('Y-m-d H:i:s', strtotime($row['contact_date']));
			$row['team_name'] = $team_list[$row['team_code']];
			$list[] = $row;
		}

		$datum = array(
			'list'=>$list
		);
		$this->_render('tab_contact', $datum, 'inc');
	}

	function tab_pay() {
		$p = $this->param;

		$treat_region = $this->common_lib->get_cfg('treat_region');
		$treat_item = $this->common_lib->get_cfg('treat_item');
		$op_type = $this->common_lib->get_cfg('op_type');

		$this->load->model('Patient_Model');
		$this->load->library('patient_lib');

		$where = array('p.cst_seqno'=>$p['cst_seqno'],'pp.is_delete'=>'N'); //검색조건설정
		$rs = $this->Patient_Model->select_project_all($where, 'pp.*');

		// pre($rs);
		if($rs) {
			$list = array();
			$idx = count($rs);
			foreach($rs as $row) {
				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['date_insert'] = substr($row['date_insert'],0,10);

				$row['op_type_txt'] = $op_type[$row['op_type']];

				//진료정보
				$row['treat_nav_long'] = ($row['treat_cost_no'])?$this->patient_lib->set_treat($row['treat_cost_no'], 'text', 'long'):'';

				if($row['discount_amount']>0) $row['contrast']='-';
				else if($row['discount_amount']<0) $row['contrast']='+';
				else $row['contrast']='';
				//$row['contrast'] = ($row['discount_amount']>0)?'-':'+';
				$row['contrast_rate'] = ($row['discount_rate']==100)?$row['contrast'].'100':$row['contrast'].abs($row['discount_rate']);
				$row['amount_total'] = $row['amount_basic']+$row['amount_addition'];
				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);
				$row['idx'] = $idx--;
				$list[] = $row;
			}
		}

		$datum = array(
			'list'=>$list
		);

		$this->_render('tab_pay', $datum, 'inc');
	}

	/**
	 * 상담상세 > 예약기록
	 * @return [type] [description]
	 */
	function tab_appointment() {
		$page = 1;
		$limit = 100;
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

		$this->load->model('Patient_Model');
		$this->load->library('patient_lib');
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

		$datum = array(
			'list'=>$list
		);

		// pre($list);
		$this->_render('tab_appointment', $datum, 'inc');
	}



	private function _get_neighbor($cst_seqno, $mode) {

		if (get_cookie('srch_name') != '') $where['name like '] = "%" . $_GET['srch_name'] . "%";
		if (get_cookie('srch_cst_status') != '') $where['cst_status'] = get_cookie('srch_cst_status');
		if (get_cookie('srch_main_category') != '') $where['main_category'] = get_cookie('srch_main_category');
		if (get_cookie('srch_complain_status') != '') $where['complain_status'] = get_cookie('srch_complain_status');
		if (get_cookie('srch_path') != '') $where['path'] = get_cookie('srch_path');
		if (get_cookie('srch_tel') != '') $where['tel like '] = "%" . str_replace( '-', '', get_cookie('srch_tel') ) . "%";
		if (get_cookie('srch_start_date') != '') $where['reg_date >='] = str_replace( '-', '', get_cookie('srch_start_date') ) . '000000';
		if (get_cookie('srch_end_date') != '') $where['reg_date <='] = str_replace( '-', '', get_cookie('srch_end_date')) . '999999';
		if (get_cookie('srch_oper_date') != '') $where['oper_date like '] = "%" . get_cookie('srch_oper_date') . "%";
		if (get_cookie('srch_start_sales') != '') $where['sales >= '] = trim(get_cookie('srch_start_sales'));
		if (get_cookie('srch_end_sales') != '') $where['sales <= '] = trim(get_cookie('srch_end_sales'));
		if (get_cookie('srch_charge_date') != '') $where['charge_date <= '] = str_replace( '-', '', get_cookie('srch_charge_date') ) . '999999';
		if (get_cookie('srch_permanent_status') != '') $where['permanent_status'] = get_cookie('srch_permanent_status');
		if (get_cookie('srch_charge_user_id') != '') $where['charge_user_id'] = get_cookie('srch_charge_user_id');
		if (get_cookie('srch_team_code') != '') $where['team_code'] = get_cookie('srch_team_code');

		if ($mode == 'PREV') {
			$where['cst_seqno < '] = $cst_seqno;
			$order_option = 'DESC';
		} else if ($mode == 'NEXT') {
			$where['cst_seqno > '] = $cst_seqno;
			$order_option = 'ASC';
		}

		$row = $this->Consulting_model->get_neighbor($where, 'cst_seqno', $order_option);
		return $row['cst_seqno'];
	}



	public function log_lists($cst_seqno) {
		$team_list = $this->User_model->get_team_list( '90' );
		$result = $this->Consulting_model->get_log_list( $cst_seqno );
		$no = count( $result );
		foreach ( $result as $i => $row ) {
			$list->rows[$i]['id'] = $row['seqno'];
			$list->rows[$i]['cell'] = array (
					$no --,
					set_long_date_format( '-', $row['reg_date'] ),
					$team_list[$row['team_code']],
					$row['name'],
					$row['title'],
					$row['contents']
			);
		}
		echo json_encode( $list );
	}



	public function user_main($param) {
		$this->page_title = '업무이력';

		$this->load->view( 'consulting/index', array (
				'type'=>$type,
				'team_list'=>$team_list,
				'main_category'=>$main_category
		) );
	}



	public function memo_lists($cst_seqno) {
		$result = $this->Consulting_model->get_memo_list( $cst_seqno );
		foreach ( $result as $i => $row ) {
			$list->rows[$i]['id'] = $row['seqno'];
			$list->rows[$i]['cell'] = array (
					set_long_date_format( '-', $row['reg_date'] ),
					$row['name'],
					$row['memo']
			);
		}
		echo json_encode( $list );
	}


	/**
	 * 업무이력
	 * @return [type] [description]
	 */
	public function work() {

		$search = $_SESSION['search'];
		if(empty($search)) {
			$search = array(
				'date_s'=>date('Y-m-d'),
				'date_e'=>date('Y-m-d')
			);
		}

		$team_list = $this->User_model->get_team_list( '90' ); //상담팀
		$team_member = $this->common_lib->get_user($this->session->userdata('ss_team_code'));
		// pre($team_member);
		$datum = array(
			'cfg'=>array(
				'team'=>$team_list,
				'date'=>get_search_type_date(),
				'team_member'=>$team_member
			),
			'session'=>array(
				'dept'=>$this->session->userdata('ss_dept_code')
			),
			'search' => $search
		);
		$this->_render('work',$datum);
	}

	/**
	 * 업무이력 페이징
	 * @return [type] [description]
	 */
	function work_list_paging($assoc='') {
		$p = $this->param;

		if(empty($assoc)) {
			parse_str($this->input->post('search'), $assoc);
		}

		$_SESSION['search'] = array_filter($assoc); //검색데이터 세션처리

		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		$where_offset = array();
		if($this->session->userdata("ss_dept_code") == 90) {
			$where_offset['l.team_code'] = $this->session->userdata("ss_team_code");
		}

		$where_offset['c.biz_id'] = $assoc['grant_biz'];

		$where = array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('limit', 'page', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'date_s':
					$where['l.reg_date >=']= str_replace('-','',$v).'000000';
				break;
				case 'date_e':
					$where['l.reg_date <=']= str_replace('-','',$v).'999999';
				break;
				case 'team_code':
					$where['l.team_code']=$v;
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$team_list = $this->User_model->get_team_list( '90' );
		$user_list = $this->User_model->get_team_user();

		$rs = $this->Consulting_model->select_work_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['reg_user_name'] = $user_list[$row['reg_user_id']];
				$row['team_name'] = $team_list[$row['team_code']] ;
				$row['work_datetime'] = date('Y-m-d H:i', strtotime($row['reg_date']));
				$row['cst_status_txt'] = $this->cst_status_list[$row['cst_status']];
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

	public function work_lists() {
		$user_list = $this->User_model->get_team_user();
		$team_list = $this->User_model->get_team_list( '90' );

		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];

		$first = ($page - 1) * $limit;

		$reg_date = str_replace( '-', '', $_GET['srch_date'] );
		if ($this->session->userdata( 'ss_dept_code' ) == '90') {
			$where_option = " AND B.team_code='" . $this->session->userdata( 'ss_team_code' ) . "'";
		} else {
			if (! empty( $_GET['srch_team_code'] )) $where_option = " AND B.team_code='" . $_GET['srch_team_code'] . "'";
		}
		if (! empty( $_GET['srch_date'] )) $where_option .= ' AND substring(B.reg_date, 1, 8)=' . $reg_date;
		if (! empty( $_GET['srch_charge_user_id'] )) $where_option .= " AND B.reg_user_id = '" . $_GET['srch_charge_user_id'] . "'";
		$result = $this->Consulting_model->get_contact_log( $first, $limit, $reg_date, $where_option );
		$total = $this->Consulting_model->get_total();
		foreach ( $result as $i => $row ) {
			$no = $total - $first - $i;

			$memo_total[$i] = ($row['memo_total'] > 0) ? " <span class='badge red'>" . $row['memo_total'] . "</span>" : "";

			$list->rows[$i]['id'] = $row['cst_seqno'];
			$list->rows[$i]['cell'] = array (
					$no,
					$this->path_list[$row['path']],
					set_date_format( 'm-d H:i', $row['reg_date'] ),
					$mod_date = ($row['mod_date'] != '') ? set_long_date_format( '-', $row['mod_date'] ) . $memo_total[$i] : '',
					$row['name'],
					$this->cst_status_list[$row['pre_status']],
					$row['last_status'],
					$user_list[$row['reg_user_id']],
					$team_list[$row['team_code']]
			);
		}

		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		echo json_encode( $list );
	}



	public function contact_lists($cst_seqno) {
		$team_list = $this->User_model->get_team_list( '90' );
		$where['cst_seqno'] = $cst_seqno;
		$result = $this->Consulting_model->get_contact_list( '*', $where );
		$no = count( $result );
		foreach ( $result as $i => $row ) {
			$list->rows[$i]['id'] = $row['seqno'];
			$list->rows[$i]['cell'] = array (
					$no --,
					set_long_date_format( '-', $row['contact_date'] ),
					$team_list[$row['team_code']],
					$row['name']
			);
		}
		echo json_encode( $list );
	}



	public function search_main() {
		$this->load->view( 'consulting/search' );
	}



	public function search_lists() {

		$biz_list = $this->Manage_model->get_biz_info( $this->session->userdata( 'ss_hst_code' ));

		$tel = str_replace( '-', '', trim( $this->input->post( 'srch_tel' ) ) );
		$messenger = trim( $this->input->post( 'srch_messenger' ) );

		$where = null;
		$where[] = "use_flag ='Y'";
		if ($tel != '') $where[] = "tel='$tel'";
		if ($messenger != '') $where[] = "messenger='$messenger'";

		$row = $this->Consulting_model->get_cst_result( $where );
		$row['biz_name'] = $biz_list[$row['biz_id']];
		$row['path'] = $this->path_list[$row['path']];
		$row['cst_status'] = $this->cst_status_list[$row['cst_status']];
		$row['messenger'] = set_blank($row['messenger']);
		$row['reg_date'] = set_long_date_format('-', $row['reg_date']);

		echo json_encode( $row );
	}



	public function unconfirm_total() {
		$where[] = "(accept_date is NULL OR accept_date='')";
		$total = $this->Consulting_model->get_cst_total($where);

		echo $total;
	}



	private function _valid_view_tel() {
		$is_valid = false;

		if ($this->session->userdata( 'ss_dept_code' ) == '90') {
			$is_valid = true;
		} else if (in_array($this->session->userdata( 'ss_duty_code' ), array('9', '8'))) {
			$is_valid = true;
		} else if (in_array($this->session->userdata( 'ss_team_code' ), array('32', '51'))) {
			$is_valid = true;
		}

		return $is_valid;
	}



	public function get_cst_status($cst_status) {

		switch ($cst_status) {
			case '51':	//내원예약
				$target_list = array('52', '98', '99', '13', '50','00');
				break;

			case '50':	//내원취소
				$target_list = array('51', '52', '98', '99', '13','00');
				break;

			case '52':	//내원상담
				$target_list = array('98', '99', '13','00');
				break;

			/* 2016-12-06 김동은 팀장요청
			case '98':	//수술예정
				$target_list = array('90', '99', '13','00');
				break;
			*/

			case '90':	//수술취소
				$target_list = array('98', '99', '13','00');
				break;

			case '13':	//예치금
				$target_list = array('99', '13','00');
				break;

			/* 2016-12-06 김동은 팀장요청
			case '99':	//수술완료
				$target_list = array('99','00');
				break;
			*/

			default:
				$target_list = array_keys($this->cst_status_list);
				break;
		}

		array_push($target_list, $cst_status);
		foreach ($target_list as $i=>$val) {
			$list[$val] = $this->cst_status_list[$val];
		}

		echo json_encode( $list );
	}



	public function search_customer() {

		$where = null;
		$term = trim( strip_tags( $_GET['term'] ) );
		$where['name like '] = "%" . $term . "%";
		if ($this->session->userdata( 'ss_dept_code' ) == '90') $where['team_code'] = $this->session->userdata( 'ss_team_code' );
		$result = $this->Consulting_model->get_cst_list( 0, 100, 'all', $where, 'name', 'ASC' );
		foreach ( $result as $i => $row ) {
			$tel[$i] = set_blind( 'phone', tel_check( $row['tel'], '-' ) );
			$row['value'] = $row['name']." / ".$tel[$i];
			$row['id'] = $row['cst_seqno'];
			$row_set[] = $row;
		}
		echo json_encode( $row_set );
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "consulting/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
