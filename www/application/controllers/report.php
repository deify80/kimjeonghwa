<?php
/**
 * 보고서
 * 작성 : 2015.06.29
 * @author 이혜진
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Report extends CI_Controller {


	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;

		$this->load->library('report_lib');
		$this->load->model( array (
			'Report_Model'
		) );

		$this->status =  $this->report_lib->sign_status;
		$this->report_status = $this->report_lib->report_status;

		$this->doc = $this->common_lib->get_code_item('10');
		$this->doc_path = '/work/report/template/';

		$this->load->library('report_lib');

		session_start();
	}

	function _get_code_by_group($group) {
		switch ($group) {
			case 'consulting':
				$report_code = array('D003','W003');
			break;
			case 'coordi':
				$report_code = array('D002');
			break;
			case 'accounting':
				$report_code = array('');
				break;
			case 'marketting':
				$report_code = array('D001','W001','W002');
			break;
		}
		return $report_code;
	}

	function ceo(){
		$group = $this->uri->segment(3);
		if($group == 'finance') {
			$this->_ceo_finance();
		}
		else {
			$report_code = $this->_get_code_by_group($group);
			$report_info = $this->Report_Model->select_config(array('code'=>$report_code));
			$report_config = $this->Report_Model->select_config_list();

			$date = get_search_type_date();
			$datum = array(
				'cfg'=>array(
					'status'=>$this->status,
					'report'=>$report_config,
					'date'=>$date
				),
				'report_group'=>$group,
				'report'=>$report_info
			);

			$this->_render('ceo', $datum);
		}
	}

	/**
	 * 회계팀 대표님보고문서
	 * @return [type] [description]
	 */
	private function _ceo_finance() {
		$search = $_SESSION['search'];
		$cfg_days = $this->config->item( 'yoil' );
		$account = $this->Report_Model->select_account(array('biz_id'=>$this->session->userdata('ss_biz_id'), 'use_flag'=>'Y'));

		if(empty($search)) {
			$search = array(
				'plan_date'=> array(
					'year'=>date('Y'),
					'month'=>date('m'),
					'day'=>'all'
				)
			);
		}

		$plan_date = $search['plan_date'];

		$mk_start = mktime(0,0,0,$plan_date['month'], 1,$plan_date['year']);
		$mk_end = mktime(0,0,0,$plan_date['month'], date('t',$mk_start) ,$plan_date['year']);
		for($mk = $mk_start;$mk<=$mk_end;$mk+=86400) {
			$d = date('d', $mk);
			$days[$d] = array(
				'w'=>date('w',$mk),
				'w_name'=>$cfg_days[date('w', $mk)]
			);
		}

		$datum = array(
			'cfg'=>array(
				'process'=>$this->config->item('process_category'),
				'account'=>$account,
				'days'=>$days
			),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:1
		);

		$this->_render('finance', $datum);
	}

	function lists_finance_paging() {
		$p = $this->input->post(NULL, false);
		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//기본조건
		$where_offset = array(
			// 'is_delete'=>'N',
			// 'writer_id'=>$this->session->userdata('ss_user_id')
		);

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);

		$where = $where_offset = array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'plan_date':
					if($v['day'] == 'all') {
						$where["DATE_FORMAT(plan_date, '%Y%m') = '".$v['year'].$v['month']."'"]=NULL;
					}
					else {
						$where['plan_date'] = implode('-',$v);
					}
				break;
				case 'word':
					$where["(trade_customer LIKE '%{$v}%' OR info LIKE '%{$v}%')"] = null;
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$_SESSION['search'] = array_filter($assoc);//검색데이터 세션처리


		$account_type = $this->config->item( 'account_type' );
		$process_category = $this->config->item( 'process_category' );
		$currency_type = $this->config->item( 'currency_type' );
		$account = $this->Report_Model->select_account(array('biz_id'=>$this->session->userdata('ss_biz_id'), 'use_flag'=>'Y'));
		$bank = get_bank();
		// pre($where);
		$rs = $this->Report_Model->select_finance_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;
				$row['reg_date'] = date('Y-m-d', strtotime($row['reg_date']));
				$row['process_category_txt'] = $process_category[$row['process_category']];
				$row['amount'] = number_format($row['amount']);
				$account_info = $account[$row['account_seqno']];
				$account_info['account_type_txt'] = $account_type[$account_info['account_type']];
				$account_info['bank_name'] = $bank[$account_info['bank_code']];
				$account_info['currency_txt'] = $currency_type[$account_info['currency']];

				$row['account'] = $account_info;
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
			'sum'=>$rs['sum'],
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
	 * 올린(상신)보고서 리스트
	 * @return [type] [description]
	 */
	function lists_send() {
		$report_info = $this->_get_config($report_code);
		$report_config = $this->Report_Model->select_config_list();
		$date = get_search_type_date();
		$datum = array(
			'cfg'=>array(
				'status'=>$this->report_status,
				'report'=>$report_config,
				'date'=>$date
			),
			'report'=>$report_info
		);

		$this->_display('lists_send', $datum);
	}


	/**
	 * 올린보고서 페이징
	 * @return [type] [description]
	 */
	function lists_send_paging() {
		$p = $this->input->post(NULL, false);
		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = $where_offset = array();

		$where_offset = array(
			'is_delete'=>'N',
			'writer_id'=>$this->session->userdata('ss_user_id')
		);


		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( report_number LIKE '%{$v}%' )"]=NULL;
					break;
				case 'date_s':
					$where['r.date_insert >=']="{$v} 00:00:00";
					break;
				case 'date_e':
					$where['r.date_insert <=']="{$v} 23:59:59";
					break;
				default :
					$where['r.'.$k] = $v;
					break;
			}
		}


		$report_config = $this->Report_Model->select_config_list();
		$rs = $this->Report_Model->select_report_send_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;
				$row['report_name'] = $report_config[$row['report_code']]['name'];
				$row['report_status_txt'] = $this->report_status[$row['report_status']];
				$row['date_insert'] =substr($row['date_insert'],0,16);
				$row['writer_info'] = unserialize($row['writer_info']);
				$row['sign_user'] = unserialize($row['sign_user']);
				$row['sign_status'] = $this->status[$row['sign_status']];

				//의견카운팅
				$this->Report_Model->backticks = FALSE;
				$count = $this->Report_Model->select_comment_row(array('report_no'=>$row['no']), 'COUNT(*) AS cnt, SUM(IF(FIND_IN_SET("'.$this->session->userdata('ss_user_id').'",reader_id), 1,0)) AS cnt_readed');
				$row['cnt_comment'] = $count;

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

	function lists_receive() {
		$report_info = $this->_get_config($report_code);
		$report_config = $this->Report_Model->select_config_list();
		$date = get_search_type_date();
		$datum = array(
			'cfg'=>array(
				'status'=>$this->status,
				'report'=>$report_config,
				'date'=>$date
			),
			'report'=>$report_info
		);

		$this->_display('lists_receive', $datum);
	}
	/**
	 * 받은보고서 리스트 페이징
	 * @return [type] [description]
	 */
	function lists_receive_paging() {
		$p = $this->input->post(NULL, false);
		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		$where_offset = array(
			'is_delete'=>'N',
			'r.report_status '=> array('wait','ing','approved','rejected')
		);

		if(!$p['ceo']) {
			$where_offset['rs.user_id'] = $this->session->userdata('ss_user_id');
			$where_offset['rs.status >='] = 2;
		}



		switch($p['group']) {
			case 'consulting' :
			case 'coordi':
			case 'marketting':
				$where_offset['r.report_code'] = $this->_get_code_by_group($p['group']);
			break;
		}


		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = array();


		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( report_number LIKE '%{$v}%' OR title LIKE '%{$v}%')"]=NULL;
				break;
				case 'date_s':
					$where['r.date_insert >=']="{$v} 00:00:00";
				break;
				case 'date_e':
					$where['r.date_insert <=']="{$v} 23:59:59";
				break;
				case 'status':
					$where['rs.status'] = $v;
					break;
				default :
					$where['r.'.$k] = $v;
					break;
			}
		}

		$report_config = $this->Report_Model->select_config_list();



		// pre($where_offset);

		$rs = $this->Report_Model->select_report_receive_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['waiter'] = $this->report_lib->get_sign_status($row['no']);
				$row['idx'] = $idx;
				$row['report_name'] = $report_config[$row['report_code']]['name'];
				$row['status'] = $this->status[$row['sign_status']];
				$row['date_insert'] =substr($row['date_insert'],0,16);
				$row['writer_info'] = unserialize($row['writer_info']);
				$row['me_status'] = $this->status[$row['sign_status']];

				//의견카운팅
				$this->Report_Model->backticks = FALSE;
				$count = $this->Report_Model->select_comment_row(array('report_no'=>$row['no']), 'COUNT(*) AS cnt, SUM(IF(FIND_IN_SET("'.$this->session->userdata('ss_user_id').'",reader_id), 1,0)) AS cnt_readed');
				$row['cnt_comment'] = $count;

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


	/**
	 * 보고서 작성
	 * @return [type] [description]
	 */
	function input() {
		$p = $this->input->post(NULL, true);

		$report_config = $this->Report_Model->select_config_list();
		if($p['no']) {

		}
		else {
			$report_info = array(
				'report_number'=>'자동부여'
			);
		}

		$session = $this->session->userdata;
		$datum = array(
			'writer'=>array(
				'id'=>$session['ss_user_id'],
				'team'=>$session['ss_team_name'],
				'name'=>$session['ss_name'],
				'position'=>$session['ss_position_name']
			),
			'cfg'=>array(
				'report'=>$report_config,
				'status'=>$this->status
			),
			'report'=>$report_info
		);

		$this->_render('input',$datum, 'inc');
		// $this->_display('input', $datum);
	}

	function input_tpl($report = array()) {

		if(empty($report)) {
			$tpl =  $this->param['tpl'];
			$mode = ($this->param['mode'])?$this->param['mode']:'insert';
			if($mode == 'view') {
				$report_no = $this->param['report_no'];
			}
		}
		else {
			$report_cfg = $this->Report_Model->select_config_row(array('code'=>$report['report_code']));
			$tpl = $report_cfg['path_tpl'];
			$mode = 'view';
			$report_no = $report['no'];
		}



		switch ($tpl){
			case 'D003': //상담팀 일일매출보고
				$this->load->model(array('Patient_Model', 'Consulting_Model'));
				$treat_region = $this->common_lib->get_cfg('treat_region');
				$treat_item = $this->common_lib->get_cfg('treat_item');
				$path = $this->common_lib->get_cfg('path');
				$date = date('Y-m-d');
				if($mode == 'insert'){
					$where_tpl = array('pp.date_project'=>$date, 'pp.manager_team_code'=>$this->session->userdata('ss_team_code'));
					$rs = $this->Patient_Model->select_project_all($where_tpl, 'pp.*, p.name, p.sex, p.cst_seqno', 'pp.patient_no ASC, pp.no DESC');

				}
				else {
					$sub_no = unserialize($report['sub_no']);
					$rs = unserialize($report['sub_data']);
					// pre($rs);
					// $where_tpl = array('pp.no'=>$sub_no);
					// $rs = $this->Patient_Model->select_project_all($where_tpl, 'pp.*, p.name, p.sex, p.cst_seqno');
				}



				$idx = count($rs);
				foreach($rs as $row) {
					if($row['cst_seqno'] >0) {
						$consulting = $this->Consulting_Model->select_consulting_row(array('cst_seqno'=>$row['cst_seqno']), 'consulting_info', 'path, reg_date');
						$consulting['reg_date'] = date('m/d', strtotime($consulting['reg_date']));
						$consulting['path_txt'] = $path[$consulting['path']];
					}
					else {
						$consulting = null;
					}

					$row['consulting']=$consulting;

					$row['op_type_txt'] = $op_type[$row['op_type']];

					$row['treat_region_name'] = $treat_region[$row['treat_region_code']];
					$row['treat_item_name'] = $treat_item[$row['treat_item_code']];

					if($row['discount_amount']>0) $row['contrast']='-';
					else if($row['discount_amount']<0) $row['contrast']='+';
					else $row['contrast']='';
					$row['amount_total'] = $row['amount_basic']+$row['amount_addition'];
					$row['idx'] = $idx;
					$row['data'] = htmlspecialchars(serialize($row), ENT_QUOTES);
					$list[] = $row;
					$idx--;
				}

				// pre($list);
				$datum = array(
					'list'=>$list
				);
			break;
			case 'D004': //회계팀 지출결의서
				$this->load->model('Support_model');
				if($mode == 'insert'){
					$classify_out = $this->Manage_model->select_code(array('depth'=>'0', 'group_code'=>'12', 'use_flag'=>'Y')); //출금분류

					//계좌정보
					$account_rs = $this->Support_model->select_account(array('biz_id'=>$this->session->userdata('ss_biz_id'), 'use_flag'=>'Y'));
					$account = array();
					foreach($account_rs as $key=>$row){
						$row['bank_name'] = $this->bank_list[$row['bank_code']];
						$row['account_type_label'] = ($row['type']=='A')?'계좌':'현금';
						$account[] = $row;
					}

					$datum = array(
						'mode'=>$mode,
						'cfg'=>array(
							'category'=>$classify_out,
							'account'=>$account
						),
						'list'=>$list
					);
				}
				else {

					$category_list = $this->Manage_model->select_code(array('group_code'=>array('11','12'), 'use_flag'=>'Y'),'*','code');
					$process_category = $this->config->item( 'process_category' );
					$account_type = $this->config->item( 'account_type' );
					// pre($account_type);
					$finance_rs = $this->Support_model->select_finance(array('referer'=>'report','referer_no'=>$report_no));
					$list = array();
					$sum = 0;
					foreach($finance_rs as $row) {
						$row['classify_txt'] = $category_list[$row['classify_code']]['title'];
						$row['item_txt'] = $category_list[$row['classify_code']]['title'];
						$row['process_category_txt'] = $process_category[$row['process_category']];
						$row['account_type_txt'] = $account_type[$row['account_type']];
						$row['account_info'] = unserialize($row['account_info']);
						$sum += $row['amount'];
						$list[] = $row;
					}

					$datum = array(
						'sum'=>$sum,
						'mode'=>$mode,
						'list'=>$list,
						'report'=>$report
					);

					// pre($datum);
				}

				break;
			default :
				$datum = array(
					'mode'=>$mode,
					'report'=>$report
				);
				break;
		}

		if($mode == 'insert') {
			$this->_render('doc/'.$tpl, $datum, 'inc');
		}
		else {
			if($this->param['report_no']) {
				$this->_render('doc/'.$tpl, $datum, 'inc');
			}
			else {
				return $this->layout_lib->fetch_("/work/report/doc/{$tpl}.html", $datum);
			}
		}

		// pre($p);
	}

	function sign_line() {
		$datum = array();
		$this->_display('sign_line', $datum);
	}

	function sign_line_list($return_type = 'json') {
		$where = array(
			'user_id'=>$this->session->userdata('ss_user_id')
		);
		$rs = $this->Report_Model->select_signline($where, 'no, line_name');

		if($return_type=='json') {
			return_json(true, '', $rs);
		}
		else return $rs;
	}

	/**
	 * 결재선 지정 결재자
	 * @return [type] [description]
	 */
	function sign_line_users() {
		$line_no = $this->param['line_no'];
		$where = array(
			'no'=>$line_no
		);
		$rs = $this->Report_Model->select_signline_row($where, 'person_approval,person_reference');

		$this->load->model('User_Model');
		$user_list = $this->User_Model->get_team_user();

		$approval_list = array_filter(explode(',',$rs['person_approval']));
		$approval = array();
		if(is_array($approval_list)) {
			foreach($approval_list as $user_id) {
				// $approval[$user_id]=$user_list[$user_id];
				$approval[] = array('user_id'=>$user_id, 'user_name'=>$user_list[$user_id]);
			}
		}
		$reference_list = array_filter(explode(',',$rs['person_reference']));
		$reference = array();
		if(is_array($reference_list)) {
			foreach($reference_list as $user_id) {
				$reference[] = array('user_id'=>$user_id, 'user_name'=>$user_list[$user_id]);
			}
		}

		$users = array(
			'approval'=>$approval,
			'reference'=>$reference
		);

		return_json(true, '', $users);
	}

	/**
	 * 결재자 지정
	 * @return [type] [description]
	 */
	function sign() {

		$signline = $this->sign_line_list('array');
		$datum = array(
			'cfg'=>array(
				'signline'=>$signline
			)
		);

		$this->_display('sign', $datum);
	}

	function view() {

		$report_no = $this->param['no'];
		$this->report_lib->view($report_no); //확인처리

		$where = array(
			'r.no'=>$report_no
		);

		$report = $this->Report_Model->select_report_row($where);
		$report['writer_info'] = unserialize($report['writer_info']);
		$report['waiter_info'] = unserialize($report['waiter_info']);

		//문서설정
		$report_cfg = $this->Report_Model->select_config_row(array('code'=>$report['report_code']));

		$report_config = $this->Report_Model->select_config_list();
		$report['report_name'] = $report_cfg['name'];
		$report['is_waiter'] = ($report['waiter_id'] == $this->session->userdata('ss_user_id') && $report['report_status']=='ing')?true:false;
		$report['contents'] = nl2br($report['contents']);

		//결재자
		$sign_rs = $this->Report_Model->select_sign(array('report_no'=>$report_no));
		foreach($sign_rs as $row) {
			$user = unserialize($row['user_info']);
			if($row['type']=='approval') {
				$sign['approval'][] = array(
					'id'=>$row['user_id'],
					'user'=>$user['name'].' '.$user['position'],
					'status'=>$row['status'],
					'date_sign'=>substr($row['date_update'],0,10)
				);
			}
			else {
				if($user['name']) {
					$sign['reference'][] = $user['name'].' '.$user['position'].'('.$this->status[$row['status']].')';
				}

			}
		}

		//첨부파일
		$this->load->model('Common_Model');
		$file_report = $this->Common_Model->select_file_row(array('refer_table'=>'report','refer_seqno'=>$report_no, 'etc'=>'report'));
		$file_attach = $this->Common_Model->select_file(array('refer_table'=>'report','refer_seqno'=>$report_no, 'etc'=>'attach'));

		$report['file'] = array(
			'report'=>'http://'.$_SERVER['HTTP_HOST'].$file_report['file_path'].$file_report['new_name'],
			'attach'=>$file_attach
		);
		//서브데이터
		$sub = $this->input_tpl($report);

		// pre($sign);
		$datum = array(
			'me'=>array(
				'id'=>$this->session->userdata('ss_user_id')
			),
			'cfg'=>$report_cfg,
			'sign'=>$sign,
			'report'=>$report,
			'sub'=>$sub
		);


		$this->_render('view', $datum, 'inc');
	}

	function blank() {
		$this->layout = 'blank_layout';
		$this->_display('blank');
	}

	function config_info() {
		$p = $this->param;
		$config = $this->_get_config($p['code']);
		return_json(true,'',$config);
	}

	function download() {
		$this->load->helper('download');
		$this->load->model('Common_Model');
		$file_seqno = $this->uri->segment(3);

		$file = $this->Common_Model->select_file_row(array('seqno'=>$file_seqno));
		$real_path = $file['file_path'].$file['new_name'];
	    $data = file_get_contents($real_path);

	    // 한글 파일명 IE에서 깨짐 처리
	    force_download(mb_convert_encoding($file['file_name'], 'euc-kr', 'utf-8'), $data);
	}

	function comment_lists() {
		$where = array(
			'report_no'=>$this->param['report_no']
		);
		$rs = $this->Report_Model->select_comment($where);
		$list = array();
		foreach ($rs as $row) {
			$row['writer_info'] = unserialize($row['writer_info']);
			$list[] = $row;
		}
		$datum = array(
			'comment'=>$list
		);
		$this->_render('comment_lists', $datum, 'inc');
	}

	/**
	 * 보고서 설정
	 * @param  [type] $code [description]
	 * @return [type]       [description]
	 */
	function _get_config($code) {
		return $this->Report_Model->select_config_row(array('code'=>$code));
	}

	/**
	 * 뷰
	 * @param  string $tmpl 템플릿경로
	 * @param  array $datum 데이터셋
	 * @return void
	 */
	private function _display($tmpl, $datum) {
		$this->load->view('/work/report/'.$tmpl, $datum );
	}


	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "/work/report/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
