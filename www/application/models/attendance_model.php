<?php
/**
 * 근태관리
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Attendance_model extends CI_Model {

	var $tbl;
	function __construct() {
		parent::__construct();
		$this->tbl = array(
			'setting'=>'team_info',
			'log'=>'attendance_log'
		);
	}

	function update_settings($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update($this->tbl['setting'], $record);
		return $rs;
	}

	function insert_logs($record) {
		$sql = $this->db->insert_string('attendance_log', $record);

		foreach($record as $field=>$value) {
			if(in_array($field, array('date','user_id', 'date_insert'))) continue;
			$update_record[] = "{$field}='{$value}'";
		}
		$sql .=" ON DUPLICATE KEY UPDATE ".implode(', ',$update_record);

		$rs = $this->db->query($sql);
		return $rs;
	}

	function update_logs($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('attendance_log',$record);
		return $rs;
	}

	/**
	 * 근태기록 삭제
	 * @param  array $where 삭제조건
	 * @return boolean 삭제처리성공여부
	 */
	function delete_logs($where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->delete('attendance_log');
		return $rs;
	}

	/**
	 * 근태기록 반환
	 * 리스팅/페이징 처리를 위한 func
	 * @param  array  $where 검색조건
	 * @param  integer $offset 시작위치
	 * @param  integer  $limit  반환갯수
	 * @return array
	 */
	function select_logs($where, $offset=0, $limit=15) {
		$count_total = $this->db->count_all_results($this->tbl['log']);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('date','DESC');

		$this->db->select('SQL_CALC_FOUND_ROWS logs.*, user.name', FALSE);
		$this->db->from($this->tbl['log'].' AS logs');
		$this->db->join('user_info AS user', 'user.user_id = logs.user_id');

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
			'list'=>$rs->result_array('no')
		);
		return $return;
	}

	/**
	 * 근태기록 반환
	 * @param  array $where 검색조건
	 * @param  string $field 필드종류(콤마로 연결)
	 * @return array
	 */
	function select_logs_all($where, $field='*') {
		$this->common_lib->set_where($where);
		$this->db->select($field);
		$query = $this->db->get('attendance_log');
		return $query->result_array();
	}

	/**
	 * 근태기록 반환
	 * ONE RECORD 반환
	 * @param  array $where [description]
	 * @param  string $field [description]
	 * @return [type]        [description]
	 */
	function select_logs_row($where, $field='*') {
		$this->common_lib->set_where($where);
		$this->db->select($field);
		$query = $this->db->get('attendance_log');
		return $query->row_array();
	}

	/**
	 * 근태기록 반환
	 * 회원정보와 join, ONE RECORD(튜플)만 반환
	 * @param  array $where [description]
	 * @return [type]        [description]
	 */
	function select_logs_user($where) {
		$this->common_lib->set_where($where);
		$this->db->select('logs.*, user.name');
		$this->db->from($this->tbl['log'].' AS logs');
		$this->db->join('user_info AS user', 'user.user_id = logs.user_id');
		$rs = $this->db->get();
		return $rs->row_array();
	}

	function select_logs_groupby($field, $where, $group_by) {
		$this->common_lib->set_where($where);
		$this->db->select($field, false);
		$this->db->group_by($group_by);
		$query = $this->db->get('attendance_log');
		// echo $this->db->last_query();
		return $query->result_array($group_by);
	}
}
?>