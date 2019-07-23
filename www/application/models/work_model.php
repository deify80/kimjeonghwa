<?php
/**
 * 작성 : 2015.03.09
 * 수정 : 
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Work_model extends CI_Model {
	var $table = '';



	public function __construct() {
		parent::__construct();
	}



	public function get_insert_id() {
		$id = $this->db->insert_id();
		return $id;
	}



	public function get_total() {
		$result = $this->db->query( 'SELECT FOUND_ROWS() as total' );
		$total = $result->row( 0 )->total;
		return $total;
	}



	public function insert($input) {
		$result = $this->db->insert( $this->table, $input );
		return $result;
	}



	public function get_info($pk_code, $pk_value, $where = null, $select_field = '*') {
		$this->db->select( $select_field );
		$this->db->where( $pk_code, $pk_value );
		if (is_array( $where )) {
			$where_option = $this->db->set_where_option( $where );
			$this->db->where( $where_option, null, false );
		}
		$query = $this->db->get( $this->table );
		$row = $query->row_array();
		return $row;
	}



	public function update($pk_code, $pk_value, $input) {
		$this->db->where( $pk_code, $pk_value );
		$this->db->update( $this->table, $input );
	}



	public function delete($pk_code, $pk_value) {
		$this->db->where( $pk_code, $pk_value );
		$this->db->delete( $this->table );
	}



	public function get_common_list($fk_code, $fk_value, $order_field, $order_option) {
		$this->db->where( $fk_code, $fk_value );
		$this->db->order_by( $order_field, $order_option );
		$query = $this->db->get( $this->table );
		$result = $query->result_array();
		return $result;
	}


	
	public function get_common_file($refer_table, $refer_seqno, $file_no='') {
		$this->db->where( 'refer_table', $refer_table );
		$this->db->where( 'refer_seqno', $refer_seqno );
		if($file_no>0) $this->db->where( 'seqno', $file_no );
		$this->db->order_by( 'seqno', 'ASC' );
		$query = $this->db->get( 'common_file' );
		$result = $query->result_array();
		return $result;
	}
	
	
	
	public function get_biz_list($where = null, $first = 0, $limit = 0, $sidx = 'biz_seqno', $sord = 'DESC') {
		$this->db->select( 'SQL_CALC_FOUND_ROWS work_biz.*, user_info.name, dept_code, team_code', false );
		$this->db->where( 'work_biz.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		if (is_array( $where )) $this->db->where( $where, false );
		
		$this->db->order_by( $sidx, $sord );
		
		$this->db->from( 'work_biz' );
		$this->db->join( 'user_info', 'work_biz.reg_user_id = user_info.user_id' );
		
		if ($limit > 0) $query = $this->db->get( '', $limit, $first );
		else $query = $this->db->get( '' );
		// echo $this->db->last_query();
		$result = $query->result_array();
		return $result;
	}



	public function get_complain_list($where = null, $first = 0, $limit = 0, $sidx = 'seqno', $sord = 'DESC') {
		$this->db->select( 'SQL_CALC_FOUND_ROWS work_complain.*, name, tel, (select team_name from team_info where team_code=consulting_info.team_code) as team_name', false );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'use_flag', 'Y' );
		if (is_array( $where )) {
			$this->load->library('common_lib');
			$this->common_lib->set_where($where);
		}
		
		$this->db->order_by( $sidx, $sord );
		$this->db->from( 'work_complain' );
		$this->db->join( 'consulting_info', 'work_complain.cst_seqno = consulting_info.cst_seqno' );
		
		if ($limit > 0) $query = $this->db->get( '', $limit, $first );
		else $query = $this->db->get( '' );
		$result = $query->result_array();
		return $result;
	}

	public function get_paper_list($where = null, $first = 0, $limit = 0, $order_option = 'seqno DESC') {
		$this->db->select( 'SQL_CALC_FOUND_ROWS work_paper.*, team_code, name', false );
		if (is_array( $where )) $this->db->where( $where, false );
		
		$this->db->order_by( $order_option );
		$this->db->from( 'work_paper' );
		$this->db->join( 'user_info', 'work_paper.reg_user_id = user_info.user_id' );
		if ($limit > 0) $query = $this->db->get( '', $limit, $first );
		else $query = $this->db->get( '' );
		$result = $query->result_array();
		return $result;
	}

	/**
	 * 회의록 등록
	 * @param  [type] $record [description]
	 * @return [type]         [description]
	 */
	function insert_minutes($record) {
		$rs = $this->db->insert('work_minutes',$record);
		return $rs;
	}

	/**
	 * 회의록 수정
	 * @param  [type] $record [description]
	 * @param  [type] $where  [description]
	 * @return [type]         [description]
	 */
	function update_minutes($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('work_minutes',$record);
		return $rs;
	}

	function select_minutes_paging($where, $offset=0, $limit=15) {
		$count_total = $this->db->count_all_results('work_minutes');
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);

		$rs = $this->db->get('work_minutes');
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

	/**
	 * 업무보고
	 * @param  [type]  $where  [description]
	 * @param  integer $offset [description]
	 * @param  integer $limit  [description]
	 * @return [type]          [description]
	 */
	function select_bizlog_paging($where, $offset=0, $limit=15, $where_offset) {
		$this->db->where($where_offset);
		$this->db->from('work_biz AS w');
		$this->db->join('user_info AS u', 'w.reg_user_id = u.user_id');
		$count_total = $this->db->count_all_results();
		
		$where = array_merge($where_offset, $where);

		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('biz_seqno','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS w.*, u.team_code, u.dept_code, u.name', FALSE);
		$this->db->from('work_biz AS w');
		$this->db->join('user_info AS u', 'w.reg_user_id = u.user_id');
		$rs = $this->db->get();
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

	function select_minutes_row($where) {
		$this->db->where($where);
		$this->db->select('*, (type+0) AS type_code');
		$query = $this->db->get('work_minutes');
		return $query->row_array();
	}

	/**
	 * 해피콜 등록
	 * @param  [type] $record [description]
	 * @return [type]         [description]
	 */
	function insert_happycall($record) {
		$rs = $this->db->insert('work_happycall',$record);
		return $rs;
	}

	/**
	 * 해피콜 수정
	 * @param  [type] $record [description]
	 * @param  [type] $where  [description]
	 * @return [type]         [description]
	 */
	function update_happycall($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('work_happycall',$record);
		return $rs;
	}

	function select_happycall_row($where) {
		$this->db->where($where);
		$this->db->select('*');
		$query = $this->db->get('work_happycall');
		return $query->row_array();
	}

	function select_happycall_paging($where, $offset=0, $limit=15, $where_offset) {
		$this->db->where($where_offset);
		$this->db->from('work_happycall AS w');
		$this->db->join('consulting_info AS c', 'c.cst_seqno = w.cst_seqno', 'left');
		$count_total = $this->db->count_all_results();
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('w.no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS w.*', FALSE);
		$this->db->from('work_happycall AS w');
		$this->db->join('consulting_info AS c', 'c.cst_seqno = w.cst_seqno', 'left');
		$rs = $this->db->get();
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

	function select_comment($where, $field='*') {
		$this->db->select($field, $this->backticks);
		$this->db->where($where);
		$this->db->order_by('no DESC');
		$query = $this->db->get('work_paper_comment');
		return $query->result_array();
	}

	function delete_comment($where) {
		if(empty($where)) return false;
		$rs = $this->db->delete('work_paper_comment',$where);
		return $rs;
	}

	function count_comment($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('work_biz_comment');
		return $count;
	}


	// 20170308 kruddo : 일일보고>일일매출보고(상담, 코디, 피부)
	public function biz_expense_insert($input) {
	    //$rs = $this->db->insert( 'work_biz_expense', $input );
		$rs = $this->db->insert( $this->table, $input );

		if($rs) {
			return $this->db->insert_id();
		}
		else {
			return false;
		}
	}

	function get_biz_expense( $where ) {
		$this->db->select( "s.*" );
		$this->common_lib->set_where($where);
		$query = $this->db->get( 'work_biz_expense as s' );

		$res = $query->result_array();
	    return $res;
	}

	function select_biz_expense_paging($where, $offset=0, $limit=15, $where_offset=array(), $order_by='') {
		$where = array_merge($where_offset, $where);

		$this->common_lib->set_where($where);

		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$this->db->from($this->table);
		if($order_by)	$this->db->order_by($order_by);

		$rs = $this->db->get();

		$return = array(
			'list'=>$rs->result_array()
		);
		return $return;
	}

	function select_biz_expense_db($where, $offset=0, $limit=15, $where_offset=array(), $order_by='') {


		$this->common_lib->set_where($where_offset);
		$this->db->from('consulting_info AS c');
		$this->db->join('patient AS p', 'c.cst_seqno=p.cst_seqno', 'LEFT');

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		$this->db->select('SQL_CALC_FOUND_ROWS path, count(c.cst_seqno) cnt, date_format(c.reg_date, "%Y-%m-%d") regdate', FALSE);
		$this->db->group_by('path, regdate');

		$rs = $this->db->get();
		

		$return = array(
			'list'=>$rs->result_array()
		);

		return $return;

	}
	// 20170308 kruddo : 일일보고>일일매출보고(상담, 코디, 피부)

}