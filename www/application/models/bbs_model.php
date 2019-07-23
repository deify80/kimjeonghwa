<?php
/**
 * 작성 : 2014.12.17
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Bbs_model extends CI_Model {
	var $bbs_code = '';



	public function __construct() {
		parent::__construct();
	}



	public function get_config($bbs_code) {
		$this->db->select( '*' );
		$this->db->where( 'bbs_code', $bbs_code );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$query = $this->db->get( 'mgr_bbs' );
		$row = $query->row_array();
		return $row;
	}



	public function get_total() {
		$result = $this->db->query( 'SELECT FOUND_ROWS() as total' );
		$total = $result->row( 0 )->total;
		return $total;
	}



	public function get_list($where, $first, $limit, $order_field = '', $order_format = '') {
		if (is_array( $where )) {
			$where_option = $this->db->set_where_option( $where );
			$this->db->where( $where_option, null, false );
		}

		$this->db->order_by( 'notice_flag', 'asc' );
		$this->db->order_by( 'status', 'asc' );
		$this->db->order_by( 'bbs_seqno', 'desc' );
		$this->db->select( 'SQL_CALC_FOUND_ROWS bbs_info.*, name as reg_user_name, (select file_name from bbs_file where bbs_seqno=bbs_info.bbs_seqno order by seqno desc limit 1) as file', false );
		$this->db->where( 'bbs_info.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'bbs_code', $this->bbs_code );
		$this->db->from( 'bbs_info' );
		$this->db->join( 'user_info', 'bbs_info.reg_user_id = user_info.user_id ', 'left outer' );
		if ($limit > 0) {
			$query = $this->db->get( '', $limit, $first );
		} else {
			$query = $this->db->get();
		}

		$result = $query->result_array();
		return $result;
	}



	public function update_hit($bbs_seqno) {
		$this->db->set( 'hit', 'hit + 1', false );
		$this->db->where( 'bbs_seqno', $bbs_seqno );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'bbs_code', $this->bbs_code );
		$this->db->update( 'bbs_info' );
	}



	public function get_info($bbs_seqno) {
		$this->db->select( '*, (select name from user_info where user_id=bbs_info.reg_user_id) as reg_user_name' );
		$this->db->where( 'bbs_seqno', $bbs_seqno );
		$this->db->where( 'bbs_code', $this->bbs_code );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$query = $this->db->get( 'bbs_info' );
		$row = $query->row_array();
		return $row;
	}



	public function insert($input) {
		$this->db->insert( 'bbs_info', $input );
	}



	public function file_insert($input) {
		$this->db->insert( 'bbs_file', $input );
	}



	public function check_captcha($captcha, $ip) {
		$expiration = time() - 7200;
		$this->db->query( "DELETE FROM captcha WHERE captcha_time < " . $expiration );

		$sql = "SELECT COUNT(*) AS total FROM captcha WHERE word = ? AND ip = ? AND captcha_time > ?";
		$binds = array (
				$captcha,
				$ip,
				$expiration
		);
		$query = $this->db->query( $sql, $binds );
		$row = $query->row();

		return $row->total;
	}



	public function get_comment_list($bbs_seqno) {
		$this->db->select( '*, (select name from user_info where user_id=bbs_comment.reg_user_id) as reg_user_name' );
		$this->db->order_by( 'seqno', 'ASC' );
		$this->db->where( 'bbs_seqno', $bbs_seqno );
		$query = $this->db->get( 'bbs_comment' );
		$result = $query->result_array();
		return $result;
	}



	public function get_file_list($bbs_seqno) {
		$this->db->order_by( 'seqno', 'ASC' );
		$this->db->where( 'bbs_seqno', $bbs_seqno );
		$query = $this->db->get( 'bbs_file' );
		$result = $query->result_array();
		return $result;
	}



	public function comment_insert($input) {
		$result = $this->db->insert( 'bbs_comment', $input );
		return $result;
	}



	public function get_insert_id() {
		$id = $this->db->insert_id();
		return $id;
	}



	public function get_file_info($seqno) {
		$this->db->select( '*' );
		$this->db->where( 'seqno', $seqno );
		$query = $this->db->get( 'bbs_file' );
		$row = $query->row_array();
		return $row;
	}



	public function update($bbs_seqno, $input) {
		$this->db->where( 'bbs_seqno', $bbs_seqno );
		$this->db->update( 'bbs_info', $input );
	}



	public function delete_file($bbs_seqno, $seqno) {
		$this->db->where( 'seqno', $seqno );
		$this->db->where( 'bbs_seqno', $bbs_seqno );
		$this->db->delete( "bbs_file" );
	}



	public function delete($bbs_seqno) {
		$this->db->where( 'bbs_seqno', $bbs_seqno );
		$this->db->where( 'bbs_code', $this->bbs_code );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );

		$this->db->delete( "bbs_info" );
	}



	public function get_neighbor($type, $bbs_seqno) {
		$this->db->select( 'bbs_seqno' );
		$this->db->where( 'bbs_code', $this->bbs_code );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'notice_flag !=', 'Y' );

		if ($type == 'PREV') {
			$this->db->where( 'bbs_seqno < ', $bbs_seqno );
			$this->db->order_by( 'bbs_seqno', 'DESC' );
		} else if ($type == 'NEXT') {

			$this->db->where( 'bbs_seqno > ', $bbs_seqno );
			$this->db->order_by( 'bbs_seqno', 'ASC' );
		}

		$query = $this->db->get( 'bbs_info', 1 );
		$row = $query->row();
		return $row->bbs_seqno;
	}
}
