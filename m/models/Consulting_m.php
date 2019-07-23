<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Consulting_m extends CI_Model {
	public function __construct() {
		parent::__construct();

		//$this->tbl = $this->config->item('tables');
	}

	public function db_insert($record) {
		$rs = $this->db->insert('db_info',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else return false;
	}

	public function db_update($record, $where) {
		if(empty($where)) return false;
		$this->db->where($where);
		$rs = $this->db->update('db_info',$record);
		return $rs;
	}

	public function insert($record) {
		$rs = $this->db->insert('consulting_info',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else return false;
	}

	public function select_consulting_paging($where, $offset=0, $limit=15, $where_offset = array(), $order_by='cst_seqno DESC') {
		$this->common_lib->set_where($where_offset);
		$count_total = $this->db->count_all_results('consulting_info');

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}


		$this->db->select('SQL_CALC_FOUND_ROWS *, IF(ISNULL(accept_date), 1, 0) AS accept_flag ', FALSE);
		$order_by = ($order_by)?$order_by:'cst_seqno DESC';
		$this->db->order_by($order_by);
		$rs = $this->db->get('consulting_info');

		// echo $this->db->last_query();
		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;

		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			'list'=>$rs->result_array()
		);
		return $return;
	}

	function select_consulting($where, $field='*', $order_by='', $key='', $tbl="consulting_info") {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		if($order_by) $this->db->order_by($order_by);

		$query = $this->db->get($tbl);
		return $query->result_array($key);
	}

	function select_consulting_row($where, $tbl='consulting_info', $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get($tbl);
		return $query->row_array();
	}

	public function get_cst_info($cst_seqno, $field = '*') {
		$this->db->select( $field, false );
		$this->db->where( 'cst_seqno', $cst_seqno );
		$query = $this->db->get( 'consulting_info' );
		$row = $query->row_array();
		return $row;
	}

	public function get_memo_list($cst_seqno) {
		$this->db->select( 'A.*, B.name' );
		$this->db->where( 'cst_seqno', $cst_seqno );
		$this->db->where( 'A.reg_user_id', 'B.user_id', false );
		$this->db->order_by( 'seqno', 'DESC' );
		$query = $this->db->get( 'consulting_memo as A, user_info as B' );
		$result = $query->result_array();
		return $result;
	}

	public function log_insert($input) {
		$result = $this->db->insert( 'log_change', $input );
		return $result;
	}

	public function update_cst($cst_seqno, $input) {

		//charge 변경시 로그
		$old = $this->get_cst_info($cst_seqno, 'charge_date, biz_id, hst_code, team_code');

		$this->db->where( 'cst_seqno', $cst_seqno );
		$rs = $this->db->update( 'consulting_info', $input );

		if($rs) {
			$new = $this->get_cst_info($cst_seqno, 'charge_date, biz_id, hst_code, team_code');
			if($old['charge_date'] != $new['charge_date'] || ($old['team_code'] != $new['team_code'] && $new['charge_date'] > $now)) {
				$now = date('YmdHis');
				$status = ($old['charge_date'] < $now && $new['charge_date'] >= $now)?'move':'remain';

				$datum = array(
					'cst_seqno'=>$cst_seqno,
					'act'=>$status, //first:최초분배, move:(공동DB->팀DB)이동, remain:변화없음
					'hst_code'=>$new['hst_code'],
					'biz_id'=>$new['biz_id'],
					'team_code'=>$new['team_code'],
					'team_code_from'=>$old['team_code'],
					'charge_date'=>$new['charge_date']
				);
				$this->charge_log_insert($datum);
			}
		}

		return $rs;
	}

	public function memo_insert($input) {
		$result = $this->db->insert( 'consulting_memo', $input );
		return $result;
	}

	public function check_tel($tel) {
		$this->db->select( 'COUNT(tel) as total, cst_seqno, reg_date, team_code, org_team_code' );
		$this->db->where( 'tel', str_replace( '-', '', $tel ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'use_flag','Y' );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->order_by( 'cst_seqno', 'DESC' );
		$query = $this->db->get( 'consulting_info', 1 );
		$row = $query->row_array();
		return $row;
	}
}
