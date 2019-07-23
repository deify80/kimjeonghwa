<?php
/**
 * 작성 : 2014.10.17
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class User_model extends CI_Model {

	function __construct() {
		parent::__construct();
	}

	function get_info($where, $field='*') {
		$this->db->select($field);

		if (is_array( $where )) $this->db->where( $where );
		$query = $this->db->get( 'user_info' );
		$row = $query->row_array();
		return $row;
	}

	public function get_user_list($first = 0, $limit = 0, $where = null) {
		$this->db->select( 'SQL_CALC_FOUND_ROWS *', false );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		if (is_array( $where )) $this->db->where( $where, false );
		$this->db->order_by( 'reg_date', 'DESC' );
		$query = $this->db->get( 'user_info', $limit, $first );
		$result = $query->result_array();
		return $result;
	}

	function get_user_all($where, $field='*', $key='user_id') {
		$this->db->select( $field );
		$this->common_lib->set_where($where);
		$query = $this->db->get( 'user_info' );
		$list = $query->result_array($key);
		return $list;
	}

	function get_team_list($dept_code = '', $status='1', $biz_id='') {
		$this->db->select( '*' );

		if (! empty( $dept_code )) {
			if(is_array($dept_code)) {
				$this->db->where_in( 'dept_code', $dept_code );
			}
			else {
				$this->db->where( 'dept_code', $dept_code );
			}

		}
		if($status != 'all') {
			$this->db->where( 'status', $status );
		}

		if($biz_id!='all') {
			$this->db->where('biz_id',$this->session->userdata('ss_biz_id')); //메뉴등 전체팀 노출
		}

		$this->db->order_by( 'order_no', 'ASC' );
		//$this->db->order_by( 'team_name', 'ASC' );
		$query = $this->db->get( 'team_info' );
		$result = $query->result_array();

		foreach ( $result as $i => $row ) {
			$list[$row['team_code']] = $row['team_name'];
		}
		return $list;
	}

	// 20170202 kruddo dept_code가 아닌 team_code로 검색
	function get_team_code_list($dept_code = '', $status='1', $biz_id='') {
		$this->db->select( '*' );

		if (! empty( $dept_code )) {
			if(is_array($dept_code)) {
				$this->db->where_in( 'team_code', $dept_code );
			}
			else {
				$this->db->where( 'team_code', $dept_code );
			}

		}
		if($status != 'all') {
			$this->db->where( 'status', $status );
		}

		if($biz_id!='all') {
			$this->db->where('biz_id',$this->session->userdata('ss_biz_id')); //메뉴등 전체팀 노출
		}

		$this->db->order_by( 'order_no', 'ASC' );
		//$this->db->order_by( 'team_name', 'ASC' );
		$query = $this->db->get( 'team_info' );
		$result = $query->result_array();

		foreach ( $result as $i => $row ) {
			$list[$row['team_code']] = $row['team_name'];
		}
		return $list;
	}
	// 20170202 kruddo dept_code가 아닌 team_code로 검색

	/**
	 * 팀정보
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	function get_team($where) {
		if(is_array($where)) {
			foreach ($where as $k=>$v) {
				if(is_array($v)) $this->db->where_in($k,$v);
				else $this->db->where($k,$v);
			}
		}
		else {
			$this->db->where($where);
		}

		$query = $this->db->get( 'team_info' );
		$result = $query->result_array('team_code');
		return $result;
	}

	function get_team_row($where) {
		if(is_array($where)) {
			foreach ($where as $k=>$v) {
				if(is_array($v)) $this->db->where_in($k,$v);
				else $this->db->where($k,$v);
			}
		}
		else {
			$this->db->where($where);
		}

		$query = $this->db->get( 'team_info' );
		$row = $query->row_array();
		return $row;
	}


	function get_dept_list( $where = null ) {
		$this->db->select( '*' );
		if (is_array( $where )) $this->db->where( $where, false );
		$this->db->where( 'status', '1' );
		$this->db->order_by( 'order_no', 'ASC' );
		$query = $this->db->get( 'dept_info' );
		$result = $query->result_array();
		foreach ( $result as $i => $row ) {
			$list[$row['dept_code']] = $row['dept_name'];
		}
		return $list;
	}

	function get_team_user($team_code='', $dept_code='', $key_field='user_id') {
		$this->db->select( '*' );
		if (!empty($team_code)) $this->db->where( 'team_code', $team_code );
		if (!empty($dept_code)) $this->db->where( 'dept_code', $dept_code );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'status', '1' );
		$this->db->order_by( 'name', 'ASC' );
		$query = $this->db->get( 'user_info' );
		$result = $query->result_array();
		foreach ( $result as $i => $row ) {
			$list[$row[$key_field]] = $row['name'];
		}
		return $list;
	}

	function get_team_user_count(){
		$this->db->select('COUNT(*) as cnt, team_code');
		$this->db->where('status','1');
		$this->db->where('team_code is not null');
		$this->db->group_by('team_code');
		$query = $this->db->get('user_info');
		return $query->result_array('team_code');
	}

	function get_duty_user($duty_code) {
		$this->db->select( '*' );
		$this->db->where( 'duty_code', $duty_code );
		$this->db->where( 'status', '1' );
		$query = $this->db->get( 'user_info' );
		$result = $query->result_array();
		foreach ( $result as $i => $row ) {
			$list[$row['user_id']] = $row['name'];
		}
		return $list;
	}

	function get_dept_user($dept_code) {
		$this->db->select( 'user_id, name' );
		if(is_array($dept_code)) {
			$this->db->where_in( 'dept_code', $dept_code );
		}
		else {
			$this->db->where( 'dept_code', $dept_code );
		}

		$this->db->where( 'status', '1' );
		$query = $this->db->get( 'user_info' );
		$result = $query->result_array();
		foreach ( $result as $i => $row ) {
			$list[$row['user_id']] = $row['name'];
		}
		return $list;
	}



	public function insert($input) {
		$this->db->insert( 'user_info', $input );
	}


	public function insert_log($input) {
		$this->db->insert( 'log_login', $input );
	}


	public function update($user_id, $input) {
		$this->db->where('user_id', $user_id);
		$rs = $this->db->update( 'user_info', $input );
		return $rs;
	}


	public function update_in($user_id, $input) {
		$this->db->where_in('user_id', $user_id);
		$this->db->update( 'user_info', $input );
	}


	public function insert_user($record) {
		$record['reg_date'] = NOW;
		$rs = $this->db->insert('user_info',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else  return false;
	}

	public function update_user($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('user_info',$record);
		// echo $this->db->last_query();
		return $rs;
	}


	public function get_total() {
		$result = $this->db->query( 'SELECT FOUND_ROWS() as total' );
		$total = $result->row( 0 )->total;
		return $total;
	}

	function get_max() {
		$this->db->select_max('no');
		$query = $this->db->get( 'user_info' );
		$row = $query->row_array();
		return $row['no'] + 1;
	}

	/**
	 * 팀정보 변경
	 * @param  [type] $record [description]
	 * @param  [type] $where  [description]
	 * @return [type]         [description]
	 */
	function update_team($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('team_info',$record);
		return $rs;
	}

	/**
	 * 위젯 설정
	 * @param  array $where [description]
	 * @return [type]        [description]
	 */
	function select_widget($where, $field='*', $key='no') {
		$this->db->select($field);
		$this->db->order_by('sort');
		$this->db->where($where);
		$query = $this->db->get('user_widget');
		return $query->result_array($key);
	}

	function insert_widget($record) {
		$sql = $this->db->insert_string('user_widget', $record);

		foreach($record as $field=>$value) {
			if(in_array($field, array('user_no','widget'))) continue;
			$update_record[] = "{$field}='{$value}'";
		}
		$sql .=" ON DUPLICATE KEY UPDATE ".implode(', ',$update_record);

		$rs = $this->db->query($sql);
		return $rs;
	}


	function select_user_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		if(!empty($where_offset)) {
			$this->common_lib->set_where($where_offset);
			$where = array_merge($where, $where_offset);
		}
		$count_total = $this->db->count_all_results('user_info');
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('join_date','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);

		$rs = $this->db->get('user_info');
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

	function count_user($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('user_info');
		return $count;
	}

	/**
	 * 인건비
	 * @return [type] [description]
	 */
	function select_user_all($where, $field='*') {
		$this->common_lib->set_where($where);
		$this->db->select($field);
		$query = $this->db->get('user_info');
		return $query->result_array('user_id');
	}
}
