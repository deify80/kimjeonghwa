<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Sms_model extends CI_Model {

	function __construct() {
		parent::__construct();
	}

	public function select_msg_row($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$row = $this->db->get("sms_msg_new");
		return $row->row_array();
	}

	public function get_msg($category) {
		$this->db->select( '*' );
		$this->db->where( 'category', $category );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$query = $this->db->get( 'sms_msg' );
		$row = $query->row_array();
		return $row;
	}



	public function insert_log($input) {
		$this->db->insert( 'sms_send_log', $input );
	}



	public function get_list($where) {
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'user_id', $this->session->userdata( 'ss_user_id' ) );
		if (is_array( $where )) $this->db->where( $where, false );
		$this->db->order_by( 'idx', 'DESC' );
		$query = $this->db->get( 'sms_send_log', 50 );
		$result = $query->result_array();
		return $result;
	}

	function count_log($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('sms_send_log');
		return $count;
	}

	public function select_msg_type($where) {
		$this->db->select('no, sms_name');
		$this->db->where($where);
		$query = $this->db->get('sms_msg_new');
		$rs = $query->result_array('idx');
		return $rs;
	}

	public function select_log_paging($where, $offset=0, $limit=15, $where_offset = array()) {
		$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('sms_send_log');


		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('idx','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$rs = $this->db->get('sms_send_log');
		//echo $this->db->last_query();

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			// 'result'=>array('no'=>$search_no['no']),
			'list'=>$rs->result_array('team_code')
		);
		return $return;
	}

	//SMS메시지 설정
	public function save_sms($record, $where) {
		$rs = $this->db->update('sms_msg_new', $record, $where);

		return $rs;
	}
}
