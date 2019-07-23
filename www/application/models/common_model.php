<?php if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Common_model extends CI_Model {

	function __construct() {
		parent::__construct();
	}

	public function update_config($record) {
		$sql = $this->db->insert_string('common_config', $record);

		foreach($record as $field=>$value) {
			if(in_array($field, array('pack', 'field', 'date_insert'))) continue;
			$update_record[] = "{$field}='{$value}'";
		}
		$sql .=" ON DUPLICATE KEY UPDATE ".implode(', ',$update_record);
		$rs = $this->db->query($sql);
		return $rs;
	}

	public function select_config($where) {
		$this->db->where($where);
		$query = $this->db->get('common_config');
		$rs = $query->row_array();

		$arr = @unserialize($rs['value']);
		$return = ($arr !== false) ? $arr : $rs['value'];
		return $return;
	}

	public function insert_file($record) {
		$rs = $this->db->insert('common_file',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else  return false;
	}

	public function delete_file($where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->delete('common_file');
		return $rs;
	}

	public function select_file($where, $field='*') {
		$this->db->where($where);
		$this->db->select($field);
		$query = $this->db->get('common_file');
		return $query->result_array();
	}

	public function select_file_row($where) {
		$rs = $this->select_file($where);
		return array_shift($rs);
	}
}