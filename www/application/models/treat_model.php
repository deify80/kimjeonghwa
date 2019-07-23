<?php
/**
 * 진료관리 Model
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Treat_model extends CI_Model {

	function __construct() {
		parent::__construct();

	}

	function select($where, $field='*', $tbl="treat_notice", $key='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get($tbl);
		// echo $this->db->last_query();
		return $query->result_array($key);
	}

	function insert($record, $tbl='treat_notice') {
		$rs = $this->db->insert($tbl, $record);
		if($rs) {
			return $this->db->insert_id();
		}
		else  return false;
	}

	function update($record, $where, $tbl='treat_notice') {
		$this->common_lib->set_where($where);
		$rs = $this->db->update($tbl,$record);
		return $rs;
	}


	function delete($where, $tbl='treat_notice'){
		$this->common_lib->set_where($where);
		$rs = $this->db->delete($tbl);
		return $rs;
	}

	function insert_holiday($record) {

		$sql = $this->db->insert_string('treat_holiday', $record);

		foreach($record as $field=>$value) {
			if(in_array($field, array('date', 'date_insert'))) continue;
			$update_record[] = "{$field}='{$value}'";
		}
		$sql .=" ON DUPLICATE KEY UPDATE ".implode(', ',$update_record);

		$rs = $this->db->query($sql);
		return $rs;
	}

	function select_cost($where, $field, $order_by='no ASC'){
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->order_by($order_by);
		$query = $this->db->get("treat_cost");

		return $query->result_array('no');
	}

	function select_cost_field($where, $field, $order_by='no ASC'){
		$rs = $this->select_cost($where, 'no,'.$field, $order_by);
		$list = array();
		foreach($rs as $k=>$v) {
			$list[$k] = $v[$field];
		}
		return $list;
	}

	function select_cost_row($where, $field, $order_by='no ASC'){
		$rs = $this->select_cost($where, $field, $order_by);
		return array_shift($rs);
	}

	function select_cost_children($no) {
		$sql = "SELECT group_concat(no) as no FROM treat_cost WHERE CONCAT('_',route,'_') LIKE '%\_{$no}\_%'";
		$query = $this->db->query($sql);
		$row = $query->row_array();

		return $row['no'];
	}

	function select_max($where, $field, $tbl="treat_cost") {
		$this->db->select_max($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get($tbl);
		return $query->row_array();
	}

	function select_count($where, $tbl="treat_cost") {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results($tbl);
		return $count;
	}
}
