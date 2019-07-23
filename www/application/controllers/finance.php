<?php
/**
 * 작성 : 2015.01.15
 * 수정 : 2015.03.03
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Finance extends CI_Controller {

	public function __construct() {
		parent::__construct();
		
		$this->load->model( array (
				'Support_model',
				'Manage_model' 
		) );
		
		$this->yield = TRUE;
		
		$this->account_type = $this->config->item( 'account_type' );
		$this->process_category = $this->config->item( 'process_category' );
		$this->currency_type = $this->config->item( 'currency_type' );
		
		$this->bank_list = get_bank();
		$this->card_list = get_card();

		$this->param = $this->input->post(NULL, true);
		
		// 인사 resource 재무 finance 재고 stock
	}


	private function _trant_currency($key='') {
		$currency = array('K'=>'KRW(원화)','U'=>'USD(미국달러)', 'C'=>'CNY(중국위안)');
		if(array_key_exists($key, $currency)) {
			return $currency[$key];
		}
		else return $currency;
	}

	public function main() {
		$this->page_title = "입출금 관리";
		
		$tmp_account_list = $this->_get_valid_account();
		$account_list[] = ':-선택-';
		foreach ( $tmp_account_list as $i => $val ) {
			$account_list[] = $i . ':' . $val;
		}
		
		foreach ( $this->process_category as $i => $val ) {
			$process_list[] = $i . ':' . $val;
		}
		
		foreach ( $this->currency_type as $i => $val ) {
			$currency_list[] = $i . ':' . $val;
		}
				
		$data = array (
				'account_list'=>implode( ';', $account_list ),
				'process_list'=>implode( ';', $process_list ),
				'bank_list'=>$this->bank_list 
		);
		$this->load->view( 'support/finance/statement', $data );
	}

	/**
	 * 입출금관리
	 * @return [type] [description]
	 */
	function inout() {
		$search = $_SESSION['search'];
		$cfg_days = $this->config->item( 'yoil' );
		$account = $this->Support_model->select_account(array('biz_id'=>$this->session->userdata('ss_biz_id'), 'use_flag'=>'Y'));
		
		if(empty($search)) {
			$search = array(
				'plan_date'=> array(
					'year'=>date('Y'),
					'month'=>date('m')
				)
			);
		}

		// pre($weeks);

		$classify_in = $this->Manage_model->select_code(array('depth'=>'0', 'group_code'=>'11', 'use_flag'=>'Y')); //입금분류
		$classify_out = $this->Manage_model->select_code(array('depth'=>'0', 'group_code'=>'12', 'use_flag'=>'Y')); //출금분류

		$bank = get_bank();
		$card = get_card();
		foreach($card as $row){
			$bank[] = $row;
		}

		$group_code = ($p['statement_type']=='I')?'11':'12'; //입금(11), 출금(12)
		$category = $this->Manage_model->select_code(array('group_code'=>$group_code, 'use_flag'=>'Y', 'depth'=>'0'));

		$datum = array(
			'cfg'=>array(
				'process'=>$this->config->item('process_category'),
				'account'=>$account,
				'date'=>get_search_type_date(),
				'bank'=>$bank,
				'classify'=> array(
					'in'=>$classify_in,
					'out'=>$classify_out,
					'all'=>array_merge($classify_in, $classify_out)
				),
				'biz'=>$this->session->userdata( 'ss_biz_list' ),
				'category'=>$category,
			),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:1
		);

		//return_json(true, '', $datum);

		$this->_render("inout", $datum);
	}

	function inout_list_paging() {
		$p = $this->input->post(NULL, false);
		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);

		//기본조건
		$where_offset = array(
			'is_delete'=>'N',
			//'fa.biz_id' => ;//$this->session->userdata('ss_biz_id')
		);
		
		$where =  array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page','plan_date', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'plan_date_s':
					$where['fs.plan_date >= '] = $v;
				break;
				case 'plan_date_e' :
					$where['fs.plan_date <= '] = $v;
				break;
				case 'account_seqno':
					$where['fs.account_seqno'] = $v;
				break;
				case 'word':
					$where["(fs.trade_customer LIKE '%{$v}%' OR info LIKE '%{$v}%')"] = null;
				break;
				case 'biz_id':
					$where_offset['fs.biz_id'] = $v;
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}


		$_SESSION['search'] = array_filter($assoc);//검색데이터 세션처리
		
		$category_list = $this->Manage_model->select_code(array('group_code'=>array('11','12'), 'use_flag'=>'Y'),'*','code');
		
		$account_type = $this->config->item( 'account_type' );
		$process_category = $this->config->item( 'process_category' );
		$currency_type = $this->config->item( 'currency_type' );
		$bank = get_bank();
		$card = get_card();
		$card_kind_list = get_cardkind();
		$biz_list = $this->session->userdata( 'ss_biz_list' );

		$result = $this->Support_model->select_account_paging(array('use_flag'=>'Y'), $offset, null, array('use_flag'=>'Y'))['list'];
		$account_list = $result;
		
		
		// pre($where);
		$rs = $this->Support_model->select_finance_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {
			
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;
				$row['reg_date'] = date('Y-m-d', strtotime($row['reg_date']));
				$row['process_category_txt'] = $process_category[$row['process_category']];
				$row['amount'] = number_format($row['amount']);
				$row['account_type_txt'] = $account_type[$row['account_type']];
				$row['bank_name'] = $bank[$row['bank_code']];
				$row['currency_txt'] = $this->_trant_currency($row['currency']);	

				$row['biz_name'] = $biz_list[$row['biz_id']];
				$row['account_bank'] = $row['account_seqno'];

				$category = array();
				if($category_list[$row['classify_code']]) {
					$category[] = $category_list[$row['classify_code']]['title'];
					$category[] = $category_list[$row['item_code']]['title'];
				}

				
				/*
				$row['card_account_name'] = '';
				$row['card_account_company'] = '';
				foreach($account_list as $acc){
					if($acc['account_seqno'] == $row['account_seqno']){
						if($acc['account_type'] == 'D'){
							$row['card_account_name'] = $acc['account_name'];//.'('.$acc['card_number'].')';
							$row['card_account_company'] = $card[$acc['bank_code']];
						}
						else{
							$row['card_account_name'] =$acc['account_name'];//.'('.$acc['account_no'].')';
							$row['card_account_company'] = $card[$acc['bank_code']];
						}
						
					}
				}
				*/
				
				$row['category'] = (empty($category))?'-':implode(' > ',$category);
				
				$row['account'] = unserialize($row['account_info']);//$row['account_info']['name'];

				$row['date_insert'] = substr($row['date_insert'],0,10);
				$list[] = $row;
				$idx--;
			}
		}

		//총액
		$sum = array();
		foreach($rs['sum'] as $row) {
			$sum[$row['statement_type']]['total'] += $row['amount'];
			$sum[$row['statement_type']][$row['classify_code']] = $row['amount'];
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
			'sum'=>$sum,
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
	 * 입출금 등록/수정
	 * @return [type] [description]
	 */
	function inout_input() {
		$p =$this->input->post(NULL, true);
		
		if($p['no']) {
			$mode = 'update';
			$rs = $this->Support_model->select_finance_row(array('seqno'=>$p['no']));
			$rs['reg_date'] = date('Y-m-d', strtotime($rs['reg_date']));
			$rs['amount'] = abs($rs['amount']);
		}
		else {
			$mode = 'insert';
			$rs = array(
				'statement_type'=>$p['statement_type'],
				'plan_date'=>date('Y-m-d')
			);
		}
		$rs['biz_id'] = $this->session->userdata( 'ss_biz_id' );

		// pre($rs);
		$group_code = ($p['statement_type']=='I')?'11':'12'; //입금(11), 출금(12)
		$category = $this->Manage_model->select_code(array('group_code'=>$group_code, 'use_flag'=>'Y', 'depth'=>'0'));

		//계좌정보
		$account_rs = $this->Support_model->select_account(array('biz_id'=>$this->session->userdata('ss_biz_id'), 'use_flag'=>'Y'));
		$account = array();
		foreach($account_rs as $key=>$row){
			$row['bank_name'] = $this->bank_list[$row['bank_code']];
			$row['currency_label'] = 	$this->_trant_currency($row['currency']);
			//$row['account_type_label'] = ($row['type']=='A')?'계좌':'현금';
			
			if($row['account_type']=='A')	$row['account_type_label'] = '계좌';
			else if($row['account_type']=='C')	$row['account_type_label'] = '현금';
			else{
				$row['account_type_label'] = '카드';
				$row['bank_name'] = $this->card_list[$row['bank_code']];
				$row['account_no'] = $row['card_number'];
			}

			$account[] = $row;
		}
		$datum = array(
			'group_code'=>$group_code,
			'cfg'=>array(
				'category'=>$category,
				'account'=>$account,
				'biz'=>$this->session->userdata( 'ss_biz_list' )
			),
			'mode'=>$mode,
			'rs'=>$rs
		);
		$this->_render("inout_input", $datum, 'inc');
	}

	/**
	 * 항목/분류설정
	 * @return [type] [description]
	 */
	function category() {
		$datum = array();
		$this->_render('category',$datum);
	}

	public function statement_lists() {
		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];
		
		$first = ($page - 1) * $limit;
		
		$where = null;
		if (! empty( $_GET['srch_account_type'] )) $where['account_type'] = $_GET['srch_account_type'];
		if (! empty( $_GET['srch_bank_code'] )) $where['bank_code'] = $_GET['srch_bank_code'];
		if ($_GET['srch_process_category'] != '') $where['process_category'] = $_GET['srch_process_category'];
		if (! empty( $_GET['srch_trade_customer'] )) $where['trade_customer like '] = "%".$_GET['srch_trade_customer']."%";
		if (! empty( $_GET['srch_start_date'] )) $where['REPLACE(A.plan_date,"-", "") >= '] = str_replace( '-', '', $_GET['srch_start_date'] );
		if (! empty( $_GET['srch_end_date'] )) $where['REPLACE(A.plan_date,"-", "") <= '] = str_replace( '-', '', $_GET['srch_end_date'] );		
		if (! empty( $_GET['srch_process_currency'] )) $where['currency'] = $_GET['srch_process_currency'];		
		
		$result = $this->Support_model->get_statement_list( $where, $first, $limit );
		$total = $this->Support_model->get_total();
		
		foreach ( $result as $i => $row ) {
			
			$in_amount = ($row['amount'] > 0) ? $row['amount'] : 0;
			$out_amount = ($row['amount'] < 0) ? abs( $row['amount'] ) : 0;
			$valid_modify = (substr($row['reg_date'], 0, 8) == date('Ymd'))? 'Y':'N';
			
			$no = $total - $first - $i;
			$list->rows[$i]['id'] = $row['seqno'];
			$list->rows[$i]['cell'] = array (
					$no,
					(is_today($row['reg_date']))?set_date_format('H:i:s',$row['reg_date']):set_date_format('Y-m-d',$row['reg_date']),
					$row['plan_date'],
					$row['account_name'],
					$this->account_type[$row['account_type']],
					$this->bank_list[$row['bank_code']],
					$row['account_no'],
					$row['trade_customer'],
					$this->currency_type[$row['currency']],						
					number_format( $in_amount ),
					number_format( $out_amount ),
					$this->process_category[$row['process_category']],
					$row['info'],
					$row['seqno'],
					'MODIFY',
					$valid_modify
			);
			
			$sum['in'] += $in_amount;
			$sum['out'] += $out_amount;
		}
		
		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		
		$list->userdata['in_amount'] = number_format($sum['in']);
		$list->userdata['out_amount'] = number_format($sum['out']);
		$list->userdata['currency'] = '합계';
		
		
		echo json_encode( $list );
	}



	public function account_main() {
		$datum = array (
			'cfg'=>array(
				'bank'=>get_bank(),
				'biz'=>$this->session->userdata( 'ss_biz_list' )
			)
		);

		$this->_render('account', $datum);
	}


	/**
	 * 계좌목록 페이징
	 * @return [type] [description]
	 */
	public function account_list_paging() {
		$p = $this->input->post(NULL, false);

		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);

		//기본조건
		$where_offset = array(
			// 'use_flag'=>'Y',
			//'biz_id' => $this->session->userdata('ss_biz_id')
		);
		$where_offset['account_type !='] = 'D';

		$where =  array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;
			switch($k) {
				case 'word':
					$where["(account_name LIKE '%{$v}%')"] = null;
				break;
				default :
					$where[$k] = $v;
				break;
			}
		}
		
		$_SESSION['search'] = array_filter($assoc);//검색데이터 세션처리
	
		$biz_list = $this->session->userdata( 'ss_biz_list' );

		$account_type = $this->config->item( 'account_type' );
		$process_category = $this->config->item( 'process_category' );
		$currency_type = $this->config->item( 'currency_type' );
		$bank = get_bank();

		// pre($where);
		$rs = $this->Support_model->select_account_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {
			
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				$row['idx'] = $idx;
				$row['biz_name'] = $biz_list[$row['biz_id']];
				$row['reg_date'] = substr($row['date_insert'], 0, 10);
				$row['account_type_txt'] = $this->account_type[$row['account_type']];
				$row['bank_name'] = $bank[$row['bank_code']];
				$row['currency_txt'] = $this->_trant_currency($row['currency']);	

				//입출금총액
				$amount = $this->Support_model->groupby_finance(array('account_seqno'=>$row['account_seqno'], 'is_delete'=>'N'), 'statement_type, SUM(amount) AS sum', 'statement_type');
				$row['amount_in'] = $amount['I']['sum'];
				$row['amount_out'] = $amount['O']['sum'];
				$row['amount_now'] = $row['balance'] + $row['amount_in'] + $row['amount_out'];
				// pre($amount);
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


	function account_input() {
		$seqno = $this->param['account_seqno'];
	
		if($seqno) {
			$mode = 'update';
			$rs = $this->Support_model->select_account_row(array('account_seqno'=>$seqno));
			$rs['biz_name'] = $this->session->userdata('ss_biz_name');
		}
		else {
			$mode = 'insert';
			$rs = array(
				'hst_code'=>$this->session->userdata('ss_hst_code'),
				'biz_name'=>$this->session->userdata('ss_biz_name'),
				'biz_id'=>$this->session->userdata('ss_biz_id'),
				'account_type'=>'A',
				'use_flag'=>'Y',
				'currency'=>'K'
			);
		}
	
		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'bank'=>get_bank(),
				'biz'=>$this->session->userdata( 'ss_biz_list' )
			),
			'rs'=>$rs
		);

		$this->_render('account_input',$datum, 'inc');
	}

	
	public function account_add() {
		$input = null;
		$input['account_name'] = $this->input->post( 'account_name' );
		$input['use_flag'] = $this->input->post( 'use_flag' );
		$input['currency'] = $this->input->post( 'currency' );
				
		$this->Support_model->table = 'finance_account';
		if ($this->input->post( 'mode' ) != 'MODIFY') {
			
			$input['biz_id'] = $this->session->userdata( 'ss_biz_id' );
			$input['hst_code'] = $this->session->userdata( 'ss_hst_code' );
			$input['account_type'] = $this->input->post( 'account_type' );
			$input['bank_code'] = $this->input->post( 'bank_code' );
			$input['account_no'] = $this->input->post( 'account_no' );			
			$input['balance'] = set_number( $this->input->post( 'balance' ) );
			$input['reg_date'] = TIME_YMDHIS;
			
			$this->Support_model->insert( $input );
			
		} else {			
			$this->Support_model->update('account_seqno', $this->input->post( 'account_seqno' ), $input);
		}
	}



	public function statement_add() {
		$statement_type = ($this->input->post( 'in_amount' ) != '') ? 'I' : 'O';
		$amount = ($this->input->post( 'in_amount' ) != '') ? set_number( $this->input->post( 'in_amount' ) ) : set_number( $this->input->post( 'out_amount' ) ) * - 1;
		
		$input = null;
		$input['plan_date'] = $this->input->post( 'plan_date' );
		$input['trade_customer'] = trim($this->input->post( 'trade_customer' ));
		$input['statement_type'] = $statement_type;
		$input['amount'] = $amount;
		$input['process_category'] = $this->input->post( 'process_category' );
		$input['info'] = trim($this->input->post( 'info' ));
		$input['account_seqno'] = $this->input->post( 'account_seqno' );
		
		$this->Support_model->table = 'finance_statement';
		if ($this->input->post( 'mode' ) == 'MODIFY') {
			$input['mod_date'] = TIME_YMDHIS;
			$input['mod_user_id'] = $this->session->userdata( 'ss_user_id' );
			$this->Support_model->update('seqno', $this->input->post( 'seqno' ), $input);
		} else {
			$input['reg_date'] = TIME_YMDHIS;
			$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
			$this->Support_model->insert( $input );
		}
	}



	private function _get_valid_account() {
		$where['use_flag'] = 'Y';
		$result = $this->Support_model->get_account_list( $where );
		foreach ( $result as $i => $row ) {
			$list[$row['account_seqno']] = $row['account_name'];
		}
		
		return $list;
	}



	public function set_account($account_seqno) {
		$this->Support_model->table = 'finance_account';
		
		$where = null;
		$where['account_seqno'] = $account_seqno;
		$row = $this->Support_model->get_info( $where );
		$row['account_type_txt'] = $this->account_type[$row['account_type']];
		$row['bank_name'] = $this->bank_list[$row['bank_code']];
		$row['currency'] = $this->currency_type[$row['currency']];
		
		echo json_encode( $row );
	}



	public function account_off() {
		foreach ( $_POST['chk'] as $i => $val ) {
			$account_seqno[] = $val;
		}
		$this->Support_model->update_account_status( $account_seqno, 'N' );
	}



	public function report($type) {
		
		$title = array('daily'=>'자금일보', 'weekly'=>'주간일보');
		$this->page_title = $title[$type];
				
		$data = array(
			'type'=>$type,
			'cfg'=>array(
				'biz'=>$this->session->userdata( 'ss_biz_list' )
			)
		);


		if ( $type == 'daily' ) :
			//$this->load->view( 'support/finance/report', $data );
			$this->_render('report', $data);
		else:
			//$this->load->view( 'support/finance/reportweek', $data );
			//$this->_render('reportweek', $data);
			$this->load->view( 'support/finance/reportweek', $data );
		endif;
	}
	
	
	function category_list() {
		$p =$this->input->post(NULL, true);
		$where = array();
		if($p['group_code']) {
			$where['group_code']=$p['group_code'];
		}
		if($p['kind'] == 'item') {
			$where['depth'] = 1;
			$where['parent_code'] = $p['parent_code'];
		}
		else {
			$where['depth'] = 0;
		}

		// pre($where);

		$rs = $this->Manage_model->select_code($where);

		if($rs) {
			return_json(true,'',$rs);
		}
		else {
			return_json(false);
		}
	}

	/**
	 * 분류추가
	 * @return [type] [description]
	 */
	function category_input() {
		$p =$this->input->post(NULL, true);

		if($p['code']) {
			$mode = 'update';
			$rs = $this->Manage_model->select_code_row(array('code'=>$p['code']));
		}
		else {
			$mode = 'insert';
			$rs = array(
				'group_code'=>$p['group_code'],
				'parent_code'=>$p['parent_code'],
				'use_flag'=>'Y'
			);
		}

		$datum = array(
			'mode'=>$mode,
			'kind'=>$p['kind'],
			'rs'=>$rs
		);

		$this->_render('category_input',$datum,'inc');
	}
	
	public function report_result($type) {
						
		if ($type == 'daily') {
			//$this->output->enable_profiler( TRUE );
			$srch_day = $_GET['srch_year'].DS.str_pad($_GET['srch_month'], 2, "0", STR_PAD_LEFT).DS.str_pad($_GET['srch_day'], 2, "0", STR_PAD_LEFT);
			//$where['fs.plan_date'] = $srch_day;
			$where['date_format(fs.date_insert, "%Y-%m-%d")='] = set_date_format('Y-m-d', $srch_day);
			$where['fa.use_flag'] = 'Y';
			$where['fs.is_delete'] = 'N';

			$biz_id = $_GET['biz_id'];
			if($biz_id != ''){
				$where['fs.biz_id'] = $biz_id;
				$where['fa.biz_id'] = $biz_id;
			}

				
			$title = '자금일보';
			//$data = $this->_get_daily_report();
			//$where[] = '';

			$account = $this->_get_report_common($where, $srch_day, 'A', $biz_id);
			$cash = $this->_get_report_common($where, $srch_day, 'C', $biz_id);
			$card = $this->_get_report_common($where, $srch_day, 'D', $biz_id);

			$datum = array(
				'account'=>$account,
				'cash'=>$cash,
				'card'=>$card
			);

			if ($_GET['output_mode'] == 'EXCEL') {
				$this->yield = FALSE;
				output_excel( iconv( 'utf-8', 'utf-8', $title . '_' . date( 'Ymd' ) ) );
			}

			$this->_render($type,$datum,'inc');
		} else if ($type == 'weekly') {
				
			$title = '주간일보';
			$datum = $this->_get_weekly_report();

			if ($_GET['output_mode'] == 'EXCEL') {
				$this->yield = FALSE;
				output_excel( iconv( 'utf-8', 'utf-8', $title . '_' . date( 'Ymd' ) ) );
			}

			$this->load->view( 'support/finance/'.$type, $datum );
		}
		
		//if ($_GET['output_mode'] == 'EXCEL') {
		//	$this->yield = FALSE;
		//	output_excel( iconv( 'utf-8', 'utf-8', $title . '_' . date( 'Ymd' ) ) );
		//}

		//return_json(true, '', $datum);
		//$this->_render($type,$datum,'inc');

		//$this->load->view( 'support/finance/'.$type, $data );
	}


	private function _get_report_common($where, $srch_day, $type, $biz_id){
		$where['fa.account_type'] = $type;

		$result = $this->Support_model->get_statement_list_new( $where, "*, fa.balance_price as fa_balance_price, fs.balance_price as fs_balance_price");
		$list = array();
		$total_list = array();

		$idx = 1;

		foreach($result['list'] as $row){

			$row['account'] = unserialize($row['account_info']);
			$row['account_card_number'] = substr(unserialize($row['account_info'])['number'], -4);
			$row['fa_balance_price'] = $row['fa_balance_price'];
			$row['fs_balance_price'] = $row['fs_balance_price'];

			if($row['statement_type'] == 'I'){
				$row['in_price'] = $row['amount'];
				$row['out_price'] = '';
			}
			else{
				$row['out_price'] = str_replace('-', '', $row['amount']);
				$row['in_price'] = '';
			}
			
			$row['idx'] = $idx;

			// 총 자금 합계
			$total_list[$row['account_seqno']]['account'] = unserialize($row['account_info']);
			if($row['statement_type'] == 'I'){
				$total_list[$row['account_seqno']]['in_price'] += $row['amount'];
				$total_list[$row['account_seqno']]['out_price'] = $total_list[$row['account_seqno']]['out_price'];
			}
			else{
				$total_list[$row['account_seqno']]['out_price'] += str_replace('-', '', $row['amount']);
				$total_list[$row['account_seqno']]['in_price'] = $total_list[$row['account_seqno']]['in_price'];
			}
			// 총자금 합계

			$list[] = $row;
			$idx++;

		}

		$query1 = "";
		$query2 = "";
		if($biz_id != ''){
			$query1 .= " and fs.biz_id='".$biz_id."'";
			$query2 .= " and fa.biz_id='".$biz_id."'";
		}

		// 어제 잔액
		$yesterday = date('Y-m-d', strtotime("-1 day", strtotime($srch_day)));
		$yesterday_balance = 0;

		$query = $this->db->query("
				select fa.balance_price fa_balance_price, fs.balance_price fs_balance_price from finance_account fa left join
					(select account_seqno, balance_price from finance_statement where seqno = 
						(select max(seqno) from finance_statement fs
							where is_delete='N' 
							and date_format(plan_date, '%Y-%m-%d') = '$yesterday'".$query1."
						)
					) fs
					on fa.account_seqno=fs.account_seqno
					and fa.use_flag='Y'
					where fa.account_type='$type'
					and date_format(fa.date_insert, '%Y-%m-%d') <= '$yesterday'".$query2."
				");
		$rs = $query->result_array();
		foreach($rs as $row){
			if($row['fs_balance_price'] == null){
				$yesterday_balance += $row['fa_balance_price'];
			}
			else{
				$yesterday_balance += $row['fs_balance_price'];
			}
		}
		// 어제 잔액

		// 오늘 잔액
		$today = $srch_day;
		$today_balance = 0;

		$query = $this->db->query("
				select fa.balance_price fa_balance_price, fs.balance_price fs_balance_price from finance_account fa left join
					(select account_seqno, balance_price from finance_statement where seqno = 
						(select max(seqno) from finance_statement fs
							where is_delete='N' 
							and date_format(plan_date, '%Y-%m-%d') = '$today'".$query1."
						)
					) fs
					on fa.account_seqno=fs.account_seqno
					and fa.use_flag='Y'
					where fa.account_type='$type'
					and date_format(fa.date_insert, '%Y-%m-%d') <= '$today'".$query2."
				");
		$rs = $query->result_array();
		foreach($rs as $row){
			if($row['fs_balance_price'] == null){
				$today_balance += $row['fa_balance_price'];
			}
			else{
				$today_balance += $row['fs_balance_price'];
			}
		}
		// 오늘 잔액


		
		$data = array(
			'yesterday_balance'=>$yesterday_balance,
			'today_balance'=>$today_balance,
			'list'=>$list,
			'total_list'=>$total_list
		);
		return $data;
	}

	/*
	private function _get_daily_report() {
		
//		$this->output->enable_profiler( TRUE );

		$yoil_list = $this->config->item( 'yoil' );
		
		$date = $_GET['srch_year'].DS.str_pad($_GET['srch_month'], 2, "0", STR_PAD_LEFT).DS.str_pad($_GET['srch_day'], 2, "0", STR_PAD_LEFT);
		
		
		$yoil = $yoil_list[date( "w", strtotime( $date ) )];
		
		//계좌
		$date = str_replace( '-', '', $date);
		//$date = $_GET['srch_year'].str_pad($_GET['srch_month'], 2, "0", STR_PAD_LEFT).str_pad($_GET['srch_day'], 2, "0", STR_PAD_LEFT);
				
		$result = $this->Support_model->get_valid_account( $date );
		foreach ( $result as $i => $row ) {
			$account_list[$i]->account_seqno = $row['account_seqno'];
			$account_list[$i]->account_name = $row['account_name'];
			$account_list[$i]->bank_name = $this->bank_list[$row['bank_code']];
			$account_list[$i]->account_no = $row['account_no'];
			$account_list[$i]->pre_balance = $row['pre_balance'];
			$account_list[$i]->currency = $row['currency'];
			$account_list[$i]->currencyname = $this->currency_type[$row['currency']];
				
			
			if ($row['account_type'] == 'A') {
				$last_account = $row['account_seqno'];
				$sum['account_balance'] += $row['cur_balance'];
				
				if ( $row['currency'] == 'K' ) :
					$sum['K']['account_balance'] += $row['cur_balanceK'];
				endif;
				if ( $row['currency'] == 'U' ) :				
					$sum['U']['account_balance'] += $row['cur_balanceU'];
				endif;				
				if ( $row['currency'] == 'C' ) :				
					$sum['C']['account_balance'] += $row['cur_balanceC'];
				endif;												
				
			}
			
			$total_list[$row['account_seqno']]['account_balance'] = $row['pre_balance'];
			$total_list[$row['account_seqno']][$row['currency']]['account_balance2'] = $row['pre_balance'];				
		}

		//입출금
		$where = null;
		//$where["SUBSTRING(A.reg_date,1,8)"] = $date;
		$where['REPLACE(A.plan_date,"-", "") = '] = $date;		
		$this->Support_model->extra_order_option = 'seqno ASC'; // 2015-03-30 추가	
		$this->db->where( 'A.biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$result = $this->Support_model->get_statement_list( $where, 0, 0, 'plan_date', 'ASC' );
		foreach ( $result as $i => $row ) {
			$key = $row['account_seqno'];
			
			$in_amount[$i] = ($row['amount'] > 0) ? $row['amount'] : '';
			$out_amount[$i] = ($row['amount'] < 0) ? abs( $row['amount'] ) : '';
			
			$list[$key][$i]->trade_customer = $row['trade_customer'];
			$list[$key][$i]->info = $row['info'];
			$list[$key][$i]->in_amount = $in_amount[$i];
			$list[$key][$i]->out_amount = $out_amount[$i];
			$list[$key][$i]->amount = $row['amount'];		
			
			$total_list[$key]['in'] += $in_amount[$i];
			$total_list[$key]['out'] += $out_amount[$i];
			$total_list[$key]['sum_amount'] += $row['amount'];

			$total_list[$key][$row['currency']]['in'] += $in_amount[$i];
			$total_list[$key][$row['currency']]['out'] += $out_amount[$i];
			$total_list[$key][$row['currency']]['sum_amount'] += $row['amount'];
				
			// 잔액이체는 제외
			if ($row['process_category'] == '3') {
				$total_list[$key]['inner_in'] += $in_amount[$i];
				$total_list[$key]['inner_out'] += $out_amount[$i];

				$total_list[$key][$row['currency']]['inner_in'] += $in_amount[$i];
				$total_list[$key][$row['currency']]['inner_out'] += $out_amount[$i];
			} else {
				$sum['in'] += $in_amount[$i];
				$sum['out'] += $out_amount[$i];

				$sum[$row['currency']]['in'] += $in_amount[$i];
				$sum[$row['currency']]['out'] += $out_amount[$i];
				
				
			}
			//잔액
			$list[$key][$i]->total_amount = $total_list[$key]['account_balance'] + $total_list[$key]['sum_amount'];
			$list[$key][$i]->total_amount = $total_list[$key][$row['currency']]['account_balance2'] + $total_list[$key][$row['currency']]['sum_amount'];			
		}

		$data = array (
				'account_list'=>$account_list,
				'daily_date'=>set_date_format('Y-m-d', $date),
				'list'=>$list,
				'total_list'=>$total_list,
				'sum'=>$sum,
				'yoil'=>$yoil,
				'last_account'=>$last_account
		);
		return $data;
	}
	*/

	private function _get_weekly_report() {
		//$this->output->enable_profiler( TRUE );
		
		$date = $_GET['srch_year'].DS.str_pad($_GET['srch_month'], 2, "0", STR_PAD_LEFT).DS.str_pad($_GET['srch_day'], 2, "0", STR_PAD_LEFT);
		$date = get_week_date($date);
		
		$where = null;
		$where["REPLACE(A.plan_date,'-', '') >= "] = $date['start'];
		$where["REPLACE(A.plan_date,'-', '') <= "] = $date['end'];
		$where['process_category !='] = '3';
		$result = $this->Support_model->get_statement_list( $where, 0, 0, 'plan_date', 'ASC' );

		foreach ( $result as $i => $row ) {
			$in_amount[$i] = ($row['amount'] > 0) ? $row['amount'] : '';
			$out_amount[$i] = ($row['amount'] < 0) ? abs( $row['amount'] ) : '';
			
			$list[$i]->account_seqno = $row['account_seqno'];
			//$list[$i]->reg_date = set_date_format( '/', $row['reg_date'] );
			$list[$i]->reg_date = $row['plan_date'];
			$list[$i]->trade_customer = $row['trade_customer'];
			$list[$i]->info = $row['info'];
			$list[$i]->in_amount = $in_amount[$i];
			$list[$i]->out_amount = $out_amount[$i];
			$list[$i]->currency = $row['currency'];
			$list[$i]->currencyname = $this->currency_type[$row['currency']];
										
			$total_list[$row['currency']]['in'] += $in_amount[$i];
			$total_list[$row['currency']]['out'] += $out_amount[$i];
		}
		
		$data = array (
				'list'=>$list,
				'total_list'=>$total_list,
				'date'=>$date 
		);
		return $data;
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "/support/finance/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}




	////////////////////////////////// 20170328 kruddo - 회계팀 카드정보 추가
	public function card_main() {
		$datum = array (
			'cfg'=>array(
				'card'=>get_card(),
				'cardkind'=>get_cardkind(),
				'biz'=>$this->session->userdata( 'ss_biz_list' )
			)
		);

		$this->_render('card', $datum);
	}


	/**
	 * 계좌목록 페이징
	 * @return [type] [description]
	 */
	public function card_list_paging() {
		$p = $this->input->post(NULL, false);

		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);

		//기본조건
		$where_offset = array(
			// 'use_flag'=>'Y',
			//'biz_id' => $this->session->userdata('ss_biz_id')
		);
		$where_offset['account_type ='] = 'D';

		$where =  array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;
			switch($k) {
				case 'word':
					$where["(account_name LIKE '%{$v}%')"] = null;
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$_SESSION['search'] = array_filter($assoc);//검색데이터 세션처리
	
		$biz_list = $this->session->userdata( 'ss_biz_list' );

		$bank = get_bank();
		$card = get_card();
		$cardkind = get_cardkind();

		// pre($where);
		$ss  = '';
		$rs = $this->Support_model->select_account_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			// 연동 계좌
			$result = $this->Support_model->select_account_paging(array('use_flag'=>'Y'), $offset, null, array('use_flag'=>'Y'))['list'];
			$account_list = $result;
			// 연동 계좌
			
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				$row['idx'] = $idx;
				$row['biz_name'] = $biz_list[$row['biz_id']];
				$row['reg_date'] = substr($row['date_insert'], 0, 10);

				$row['bank_name'] = $bank[$row['bank_code']];
				$row['card_company_name'] = $card[$row['bank_code']];
				$row['card_kind_name'] = $cardkind[$row['card_kind_code']];

				$row['date_insert'] = date('Y-m-d', strtotime($row['date_insert']));

				$row['card_account_name'] = '';
				foreach($account_list as $acc){
					if($acc['account_seqno'] == $row['card_account_seq']){
						$row['card_account_name'] = $bank[$acc['bank_code']].'('.$acc['account_no'].')';
					}
				}
				
				//$account_seqno = $row['account_seqno'];
				//$row['account_name'] =  $account_seqno;		// $account[1]['account_no'];
				//$row['account_name'] = $bank[$row['account_seqno']];

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
			'paging'=>$paging,
			'account_list'=>$account_list
		);
		
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);	
		}
	}


	function card_input() {
		$seqno = $this->param['account_seqno'];

		$where_offset['use_flag'] = 'Y';
		//$where_offset['account_type!='] = 'D';
		$where_offset['account_type ='] = 'A';

		$where['use_flag'] = 'Y';
		$where['account_type ='] = 'A';
		$rs = $this->Support_model->select_account_paging($where, $offset, $limit, $where_offset);
		$bank = get_bank();
		$account_list = array();

		foreach($rs['list'] as $row){
			$row['bank_name'] = $bank[$row['bank_code']];
			$row['card_expire_date1'] = explode('/', $row['card_expire_date'])[0];
			$row['card_expire_date2'] = explode('/', $row['card_expire_date'])[1];

			$account_list[] = $row;
		}
	
		if($seqno) {
			$mode = 'update';
			$rs = $this->Support_model->select_account_row(array('account_seqno'=>$seqno));
			$rs['card_expire_date1'] = explode('/', $rs['card_expire_date'])[0];
			$rs['card_expire_date2'] = explode('/', $rs['card_expire_date'])[1];

			$rs['biz_name'] = $this->session->userdata('ss_biz_name');
		}
		else {
			$mode = 'insert';
			$rs = array(
				'hst_code'=>$this->session->userdata('ss_hst_code'),
				'biz_name'=>$this->session->userdata('ss_biz_name'),
				'biz_id'=>$this->session->userdata('ss_biz_id'),
				'use_flag'=>'Y',
			);
		}
	
		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'card'=>get_card(),
				'biz'=>$this->session->userdata( 'ss_biz_list' ),
				'cardkind'=>get_cardkind(),
				//'bank'=>get_account(),
				'account_list'=>$account_list,
			),
			'rs'=>$rs
		);
		//return_json(true, '', $datum);

		$this->_render('card_input',$datum, 'inc');
	}
	////////////////////////////////// 20170328 kruddo - 회계팀 카드정보 추가
}
