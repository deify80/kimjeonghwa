<?php
/**
 * 작성 : 2014.10.17
 * 수정 : 
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Assign_model extends CI_Model {
	var $table = "";


	public function __construct() {
		parent::__construct();
	}


	public function insert($input) {
		$rs = $this->db->insert( $this->table, $input );
		if($rs) {
			return $this->db->insert_id();
		}
		else {
			return false;
		}
	}

	function update_info($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('assign_info',$record);
		return $rs;
	}


	public function init_assign($biz_id, $path) {
		$this->db->update( 'assign_info', array (
				'status'=>0 
		), array (
				'biz_id'=>$biz_id,
				'path'=>$path 
		) );
	}


	public function get_list($first = 0, $limit = 0) {
		$this->db->select( 'SQL_CALC_FOUND_ROWS *, (select group_concat(team_code) from assign_order where seqno=A.seqno) as team_code', false );
		$this->db->where( 'A.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'A.biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'A.biz_id=B.biz_id', null, false );
		$this->db->where( 'A.status','1' );
		$this->db->order_by( 'seqno', 'DESC' );
		if ($limit > 0) $query = $this->db->get( 'assign_info as A, biz_info as B', $limit, $first );
		else $query = $this->db->get( 'assign_info as A, biz_info as B' );
		
		$result = $query->result_array();
		return $result;
	}

	/**
	 * db분배규칙
	 * @param  array  $where [description]
	 * @return [type]        [description]
	 */
	public function select_rule($where=array()) {
		$this->db->where($where);
		$this->db->select('i.*, GROUP_CONCAT(CONCAT(o.team_code,"_",o.order_no) ORDER BY o.order_no ASC) AS team, GROUP_CONCAT(CASE WHEN o.turn_status = "Y" THEN o.order_no END) AS order_no', FALSE);
		$this->db->from('assign_info AS i');
		$this->db->join('assign_order AS o', 'i.seqno=o.seqno');
		$this->db->group_by('o.seqno');
		$this->db->order_by('o.seqno DESC');
		$query = $this->db->get();
		$result = $query->result_array();
		// echo $this->db->last_query();
		return $result;

	}





	public function get_total() {
		$result = $this->db->query( 'SELECT FOUND_ROWS() as total' );
		$total = $result->row( 0 )->total;
		return $total;
	}


	public function get_order($path) {
		$this->db->select( 'team_code, B.seqno, order_no' );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'path', $path );
		$this->db->where( 'status', '1' );
		$this->db->where( 'turn_status', 'Y' );
		$this->db->where( 'A.seqno', 'B.seqno', false );
		
		$query = $this->db->get( 'assign_info as A, assign_order as B' );
		$row = $query->row();
		return $row;
	}


	public function update_order($seqno, $order_no, $turn_status) {
		$this->db->update( 'assign_order', array (
				'turn_status'=>$turn_status
		), array (
				'seqno'=>$seqno,
				'order_no'=>$order_no 
		) );
	}
	
	
	
	public function get_order_total($seqno) {
		
		$this->db->select( 'COUNT(*) as total' );
		$this->db->where( 'seqno', $seqno );
		
		$query = $this->db->get( 'assign_order' );
		$row = $query->row();
		$this->total = $row->total;
		
		return $row->total;
		
	}
	
	
	
	public function get_ing_status() {

		$this->db->select('path, order_no, team_code, turn_status');
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'status', '1');
		$this->db->where( 'A.seqno', 'B.seqno', false );
		$this->db->order_by( 'path, order_no', 'ASC' );
		$query = $this->db->get( 'assign_info as A, assign_order as B');
		$result = $query->result_array();
		return $result;
		
	}
	
	
	
	
	public function get_status($where) {		
		$this->db->select('org_team_code, path, count(*) as total, SUM(IF(compensation="Y",1,0)) AS compensation, biz_id', FALSE);
		if (is_array( $where )) $this->db->where( $where );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		// $this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'use_flag', 'Y');
		$this->db->order_by( 'org_team_code', 'ASC' );
		$this->db->group_by( 'org_team_code, path' );
		$query = $this->db->get( 'consulting_info');
		// echo $this->db->last_query();
		$result = $query->result_array();
		return $result;
	}
	
	
	
	public function get_status_by_option($where, $field, $order_field, $group_field) {
		$this->db->select($field, false);
		if (is_array( $where )) $this->common_lib->set_where( $where);
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		// $this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'use_flag', 'Y');
		$this->db->order_by( $order_field, 'ASC' );
		$this->db->group_by( $group_field );
		$query = $this->db->get( 'consulting_info AS A');
		$result = $query->result_array();
		return $result;
	}


	// 20170307 kruddo : DB규칙 - 자동분배규칙 수정
	function update_info_order($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('assign_order',$record);
		return $rs;
	}
	// 20170307 kruddo : DB규칙 - 자동분배규칙 수정
}
