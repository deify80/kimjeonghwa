<?php
/**
 * 작성 : 2015.01.13
 * 수정 : 
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Support_model extends CI_Model {
	var $table = '';
	var $extra_order_option = '';	


	public function __construct() {
		parent::__construct();
	}



	public function insert($input) {
		$this->db->insert( $this->table, $input );
	}



	public function get_total() {
		$result = $this->db->query( 'SELECT FOUND_ROWS() as total' );
		$total = $result->row( 0 )->total;
		return $total;
	}

	function select_account($where) {
		$this->common_lib->set_where($where);
		$query = $this->db->get('finance_account');
		return $query->result_array('account_seqno');
	}

	public function get_account_list($where = null, $first = 0, $limit = 0, $sidx = 'account_seqno', $sord = 'DESC') {
		$this->db->select( "SQL_CALC_FOUND_ROWS *, (select sum(amount) from finance_statement where account_seqno=finance_account.account_seqno and statement_type='I') as in_amount, (select sum(amount) from finance_statement where account_seqno=finance_account.account_seqno and statement_type='O') as out_amount", false );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		if (is_array( $where )) $this->db->where( $where );
		
		$this->db->order_by( $sidx, $sord );
		
		if ($limit > 0) $query = $this->db->get( 'finance_account', $limit, $first );
		else $query = $this->db->get( 'finance_account' );
		
		$result = $query->result_array();
		return $result;
	}

	function insert_account($record) {
		$rs = $this->db->insert('finance_account',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else return false;
	}

	function update_account($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('finance_account',$record);
		return $rs;
	}

	function select_account_row($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('finance_account');
		return $query->row_array();
	}

	function select_finance_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->db->where($where_offset);
		$this->db->join('finance_account AS fa', 'fs.account_seqno=fa.account_seqno');
		$count_total = $this->db->count_all_results('finance_statement as fs');

		$where = array_merge($where, $where_offset);

		//입출금액
		$this->db->select('fs.statement_type, fs.classify_code, SUM(fs.amount) AS amount', FALSE);
		
		$this->db->start_cache();
		$this->common_lib->set_where($where);
		$this->db->from('finance_statement AS fs');
		$this->db->join('finance_account AS fa', 'fs.account_seqno=fa.account_seqno');
		$this->db->stop_cache();
		$this->db->group_by('statement_type, classify_code');
		$sum = $this->db->get();
		// echo $this->db->last_query();

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}
		$this->db->order_by('fs.seqno DESC');
		$this->db->select("SQL_CALC_FOUND_ROWS fs.*, fa.bank_code, fa.account_no, fa.account_name, fa.account_type, fa.currency", FALSE);
		$rs = $this->db->get();
		$this->db->flush_cache();

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			// 'result'=>array('no'=>$search_no['no']),
			'list'=>$rs->result_array(),
			'sum'=>$sum->result_array()
		);
		return $return;
	}

	function select_finance($where, $field='*') {
		$this->db->select($field);
		$this->db->where($where);
		$query = $this->db->get('finance_statement');
		return $query->result_array();
	}

	function select_finance_row($where, $field='*') {
		$this->db->select($field);
		$this->db->where($where);
		$query = $this->db->get('finance_statement');
		return $query->row_array();
	}

	function groupby_finance($where, $field='*', $key='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->group_by('statement_type');
		$query = $this->db->get('finance_statement');
		// echo $this->db->last_query();
		return $query->result_array($key);
	}

	function insert_finance($record) {
		$rs = $this->db->insert('finance_statement',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else  return false;
	}

	function update_finance($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('finance_statement',$record);
		return $rs;
	}

	function select_account_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('finance_account');

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$where = array_merge($where, $where_offset);
		$this->common_lib->set_where($where);
		$this->db->from('finance_account');
		$this->db->order_by('account_seqno DESC');
		$this->db->select("SQL_CALC_FOUND_ROWS * ", FALSE);
		$rs = $this->db->get();

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			// 'result'=>array('no'=>$search_no['no']),
			'list'=>$rs->result_array()
		);
		return $return;
	}

	public function get_statement_list($where = null, $first = 0, $limit = 0, $sidx = 'seqno', $sord = 'DESC') {
		//$this->db->select( 'SQL_CALC_FOUND_ROWS A.*, B.account_name, B.account_no, B.bank_code, B.account_type, ((select sum(amount) from finance_statement where account_seqno=B.account_seqno and seqno<=A.seqno) + balance) as total_amount', false );
//		$this->db->select( 'SQL_CALC_FOUND_ROWS A.*, B.account_name, B.account_no, B.bank_code, B.account_type, ((select sum(amount) from finance_statement where account_seqno=B.account_seqno and REPLACE(plan_date, "-", "") <= REPLACE(A.plan_date, "-", "")) + balance) as total_amount', false );
		//2015-03-30 추가
		$this->db->select( 'SQL_CALC_FOUND_ROWS A.*, B.account_name, B.account_no, B.bank_code, B.account_type, B.currency, ((select sum(amount) from finance_statement where account_seqno=B.account_seqno and REPLACE(plan_date, "-", "") <= REPLACE(A.plan_date, "-", "")) + balance) as total_amount', false );
		
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		//$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'use_flag', 'Y' );
		
		if (is_array( $where )) $this->db->where( $where , false);
		$this->db->order_by( $sidx, $sord );
		if (!empty($this->extra_order_option)) $this->db->order_by( $this->extra_order_option );
		$this->db->from( 'finance_statement as A' );
		$this->db->join( 'finance_account as B', 'A.account_seqno = B.account_seqno' );
		
		if ($limit > 0) $query = $this->db->get( '', $limit, $first );
		else $query = $this->db->get( '' );
		
		$result = $query->result_array();
		
		return $result;
	}



	public function get_info($where) {
		$this->db->select( '*' );
		if (is_array( $where )) $this->db->where( $where );
		$query = $this->db->get( $this->table );
		$row = $query->row_array();
		return $row;
	}



	public function update_account_status($account_seqno, $status) {
		$input = null;
		$input['use_flag'] = $status;
		
		$this->db->where_in( 'account_seqno', $account_seqno );
		$this->db->update( 'finance_account', $input );
	}



	public function get_valid_account($date) {
/*
		$this->db->select( '*, ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno and REPLACE(plan_date, "-", "") < ' . $date . ') + balance) as pre_balance, ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno and REPLACE(plan_date, "-", "")<= ' . $date . ') + balance) as cur_balance', false );
*/
		$this->db->select( '*, ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno   and REPLACE(plan_date, "-", "") < ' . $date . ') + balance) as pre_balance, ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno   and REPLACE(plan_date, "-", "")<= ' . $date . ') + balance) as cur_balance , ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno AND  `currency` ="K"  and REPLACE(plan_date, "-", "") < ' . $date . ') + balance) as pre_balanceK, ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno  AND  `currency` ="K"  and REPLACE(plan_date, "-", "")<= ' . $date . ') + balance) as cur_balanceK , ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno AND  `currency` ="U"  and REPLACE(plan_date, "-", "") < ' . $date . ') + balance) as pre_balanceU, ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno  AND  `currency` ="U"  and REPLACE(plan_date, "-", "")<= ' . $date . ') + balance) as cur_balanceU , ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno AND  `currency` ="C"  and REPLACE(plan_date, "-", "") < ' . $date . ') + balance) as pre_balanceC, ((select IFNULL(sum(amount), 0) from finance_statement where account_seqno=finance_account.account_seqno  AND  `currency` ="C"  and REPLACE(plan_date, "-", "")<= ' . $date . ') + balance) as cur_balanceC', false );
		$this->db->where( 'use_flag', 'Y' );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		
		$this->db->order_by( 'account_type', 'DESC' );
		$query = $this->db->get( 'finance_account' );
		
		$result = $query->result_array();
		return $result;
	}
	
	
	
	public function update( $pk_key, $pk_value, $input) {
		$this->db->where( $pk_key, $pk_value );
		$result = $this->db->update( $this->table, $input );
		return $result;
	}


	////////////////////////////////// 20170330 kruddo - 회계팀 자금일보
	function get_statement_list_new($where = null, $field='*') {

		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->from('finance_statement AS fs');
		$this->db->join('finance_account AS fa', 'fs.account_seqno=fa.account_seqno');
		$rs = $this->db->get();

		$return = array(
			'list'=>$rs->result_array()
		);
		
		return $return;

	}



	////////////////////////////////// 20170328 kruddo - 회계팀 카드정보 추가
	/*
	function insert_card($record) {
		$rs = $this->db->insert('finance_card',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else return false;
	}

	function update_card($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('finance_card',$record);
		return $rs;
	}

	function select_card_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		//$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('finance_card');

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$where = array_merge($where, $where_offset);
		$this->common_lib->set_where($where);
		$this->db->from('finance_card');
		$this->db->order_by('card_seqno DESC');
		$this->db->select("SQL_CALC_FOUND_ROWS * ", FALSE);
		$rs = $this->db->get();

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			// 'result'=>array('no'=>$search_no['no']),
			'list'=>$rs->result_array()
		);
		return $return;
	}

	function select_card_row($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('finance_card');
		return $query->row_array();
	}
	*/
	////////////////////////////////// 20170328 kruddo - 회계팀 카드정보 추가
}
