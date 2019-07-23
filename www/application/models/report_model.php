<?php
/**
 * 보고서 Model
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Report_model extends CI_Model {
	var $backticks = true;
	function __construct() {
		parent::__construct();

	}

	function select_config_row($where, $field='*') {
		$rs = $this->select_config($where, $field);
		return array_shift($rs);
	}

	function select_config($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('report_config');
		return $query->result_array();
	}

	function select_config_list($where=array(), $field='*') {
		$this->common_lib->set_where($where);
		$this->db->select('code,name');
		$this->db->order_by('sort ASC');
		$query = $this->db->get('report_config');
		return $query->result_array('code');
	}

	function select_report_row($where) {
		$this->common_lib->set_where($where);
		$this->db->select('r.*, rs.status AS sign_status, rs.user_id AS waiter_id, rs.user_info AS waiter_info');
		$this->db->from('report AS r');
		$this->db->join('report_sign AS rs', 'r.sign_no=rs.no');
		$query = $this->db->get();
		return $query->row_array();
	}
	function select_report_send_paging($where, $offset=0, $limit=15, $where_offset=array()) {
	
		$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('report');
	

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS r.*, rs.status AS sign_status, rs.user_info AS sign_user', FALSE);
		$this->db->from('report AS r');
		$this->db->join('report_sign AS rs', 'r.sign_no=rs.no');
		$rs = $this->db->get();

		// echo $this->db->last_query();
		
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

	function select_report_receive_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		
		$this->common_lib->set_where($where_offset);
		$this->db->from('report AS r');
		$this->db->select('COUNT(DISTINCT(r.report_number)) AS count_total', FALSE);
		$this->db->join('report_sign AS rs', 'r.no=rs.report_no');
		
		$query = $this->db->get();
		$rs = $query->row_array();
		$count_total = $rs['count_total'];

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS r.*, rs.type AS sign_type,  rs.status AS sign_status, rs.user_info AS sign_user', FALSE);
		$this->db->from('report AS r');
		$this->db->join('report_sign AS rs', 'r.no=rs.report_no');
		$this->db->group_by('r.report_number');
		$rs = $this->db->get();
		// echo $this->db->last_query();

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

	

	function select_account($where) {
		$this->common_lib->set_where($where);
		$query = $this->db->get('finance_account');
		return $query->result_array('account_seqno');
	}


	function count($where, $tbl='report') {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results($tbl);
		return $count;
	}


	function insert($record, $tbl='report') {
		$rs = $this->db->insert($tbl,$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else  return false;
	}

	function update($record, $where, $tbl='report') {
		$this->common_lib->set_where($where);
		$rs = $this->db->update($tbl,$record);
		return $rs;
	}

	function set_update($record, $where, $tbl='report') {
		foreach($record as $field=>$value) {
			$this->db->set($field, $value, FALSE);
		}
		$this->common_lib->set_where($where);
		$rs = $this->db->update($tbl);
		return $rs;
	}

	function delete($where, $tbl='report'){
		$rs = $this->db->delete($tbl,$where);
		return $rs;
	}

	function select_sign($where, $order_by='step ASC') {
		$this->db->where($where);
		$this->db->order_by($order_by);
		$query = $this->db->get('report_sign');
		return $query->result_array();
	}

	function select_sign_row($where, $order_by='step ASC') {
		$list = $this->select_sign($where);
		return array_shift($list);
	}

	function select_signline($where, $field='*') {
		$this->db->where($where);
		$this->db->select($field);
		$query = $this->db->get('report_sign_line');
		return $query->result_array();
	}

	function select_signline_row($where, $field='*') {
		$rs = $this->select_signline($where, $field);
		return array_shift($rs);
	}

	function select_comment($where, $field='*') {
		$this->db->select($field, $this->backticks);
		$this->db->where($where);
		$this->db->order_by('no DESC');
		$query = $this->db->get('report_comment');
		return $query->result_array();
	}

	function select_comment_row($where, $field='*') {
		$list = $this->select_comment($where, $field);
		return array_shift($list);
	}
}