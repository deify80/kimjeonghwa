<?php

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Manage_m extends CI_Model {



	public function __construct() {
		parent::__construct();
	}

	public function get_biz_list() {
		$this->db->select( '*' );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$query = $this->db->get( 'biz_info' );
		$result = $query->result_array();
		return $result;
	}



	public function get_biz_info($hst_code, $use_flag='') {
		$this->db->select( '*' );

		$this->db->where( 'hst_code', $hst_code );
		if ($use_flag !='') $this->db->where( 'use_flag', $use_flag );
		$this->db->order_by('sort','ASC');
		$query = $this->db->get( 'biz_info' );
		$result = $query->result_array();
		foreach ( $result as $i => $row ) {
			$list[$row['biz_id']] = $row['biz_name'];
		}
		return $list;
	}



	public function get_hst_code($host='') {
		$host =
		$host = strtolower( trim(str_replace('m.','',$_SERVER['HTTP_HOST'] ) ));
		$this->db->select( 'hst_code' );

		$this->db->where( 'url', $host );
		$query = $this->db->get( 'hospital_info' );
		$row = $query->row_array();
		if (is_array( $row )) $hst_code = $row['hst_code'];
		return $hst_code;
	}



	public function get_hst_info() {
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$query = $this->db->get( 'hospital_info' );
		$row = $query->row_array();
		return $row;
	}



	public function update($table, $pk_key, $pk_value, $input) {
		$this->db->where( $pk_key, $pk_value );
		$result = $this->db->update( $table, $input );
		return $result;
	}



	public function get_code_item($group_code = '', $parent_code = '', $return='title', $where=array()) {
		$this->db->select( '*' );
		$this->common_lib->set_where($where);
		$this->db->where( 'use_flag', 'Y' );
		if (! empty( $group_code )) $this->db->where( 'group_code', $group_code );
		if (! empty( $parent_code )) $this->db->where( 'parent_code', $parent_code );
		if (! empty( $parent_code )) $this->db->where( 'parent_code', $parent_code );
		$this->db->order_by( 'order_no ASC, code ASC' );
		$query = $this->db->get( 'code_item' );
		$result = $query->result_array();

		// if($return == 'all') {return $result;

		$list = array();
		foreach ( $result as $i => $row ) {
			if($return == 'all') {
				if(!$etc = unserialize($row['etc'])) {
					$etc = $row['etc'];
				}
				$row['etc'] = $etc;

				$list[$row['code']] = $row;
			}
			else {
				$list[$row['code']] = $row['title'];
			}
		}
		return $list;
	}

	/**
	 * 코드추가
	 * @param  array $record [description]
	 * @return boolean        [description]
	 */
	public function insert_code_item($record) {
		$rs = $this->db->insert('code_item',$record);
		if($rs) {
			return true;
		}
		else  return false;
	}

	/**
	 * 코드 수정
	 * @param  array $record [description]
	 * @param  array $where  [description]
	 * @return boolean         [description]
	 */
	public function update_code_item($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('code_item',$record);
		return $rs;
	}

	public function duplicate_code_item($record) {
		$sql = $this->db->insert_string('code_item', $record);

		foreach($record as $field=>$value) {
			if(in_array($field, array('group_code','hst_code','biz_id'))) continue;
			$update_record[] = "{$field}='{$value}'";
		}
		$sql .=" ON DUPLICATE KEY UPDATE ".implode(', ',$update_record);
		// echo $sql;exit;
		$rs = $this->db->query($sql);
		return $rs;
	}

	public function count_code($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('code_item');
		return $count;
	}

	public function select_code_row($where, $field="*") {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('code_item');
		return $query->row_array();
	}

	public function select_code($where, $field='*', $key='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('code_item');
		return $query->result_array($key);
	}


	public function get_ip($ip, $hst_code) {
		$this->db->select( 'COUNT(*) as total' );
		$this->db->where( 'ip', trim( $ip ) );
		$this->db->where( 'use_flag', 'Y' );
		$this->db->where( 'hst_code', $hst_code );
		$query = $this->db->get( 'mgr_ip' );
		// echo $this->db->last_query();
		$row = $query->row();
		$this->total = $row->total;
		return $row->total;
	}

	public function get_ip_list() {
		$this->db->select( '*' );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->order_by( 'seqno', 'DESC' );
		$query = $this->db->get( 'mgr_ip' );
		$result = $query->result_array();
		return $result;
	}


	public function insert($table, $input) {
		$this->db->insert( $table, $input );
	}



	public function update_in($table, $pk_code, $pk_value, $input) {
		$this->db->where_in( $pk_code, $pk_value );
		$this->db->update( $table, $input );
	}



	public function get_menu_list($where=array()) {
		$this->db->select( '*' );
		$this->db->order_by( 'order_no', 'ASC' );
		$query = $this->db->get( 'mgr_menu' );
		$result = $query->result_array();
		return $result;
	}


	public function get_access_list($menu_seqno='') {
		$this->db->select( '*' );
		if ($menu_seqno != '') $this->db->where( 'menu_seqno', $menu_seqno);
		$this->db->order_by( 'menu_seqno', 'ASC' );
		$this->db->order_by( 'category', 'ASC' );
		$query = $this->db->get( 'mgr_access' );
		$result = $query->result_array();
		return $result;
	}



	public function set_access_init($menu_seqno) {
		$this->db->where( 'menu_seqno', $menu_seqno);
		$this->db->delete('mgr_access');
	}

	/**
	 * 메뉴정보
	 * @param  string $meno_no 메뉴PK or all(전체메뉴)
	 * @return array          [description]
	 */
	public function get_menu($menu_no='all') {
		if($menu_no!='all') {
			$this->db->where('no',$menu_no);
		}
		$this->db->where('is_use','Y');
		$this->db->order_by('depth','asc');
		$this->db->order_by('sort','asc');
		$query = $this->db->get('mgr_menu_new');
		return $query->result_array();
	}

	public function get_menu_row($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);

		$query = $this->db->get('mgr_menu_new');
		// echo $this->db->last_query();
		return $query->row_array();
	}

	/**
	 * 관리가능 메뉴
	 * @param  [type] $duty    [description]
	 * @param  [type] $dept    [description]
	 * @param  [type] $team    [description]
	 * @param  [type] $user_no [description]
	 * @return [type]          [description]
	 */
	public function get_grant_user($duty, $dept, $team, $user_no) {

		$this->db->select('no');

		//직책체크
		$this->db->or_like("grant_duty", ",{$duty},");
		$this->db->or_like("grant_dept", ",all,");

		//부서체크
		if($dept) {
			$this->db->or_like("grant_dept", ",{$dept},");
			if($team){
				$this->db->or_like("grant_dept", ",{$dept}_{$team},");
				if($user_no) {
					$this->db->or_like("grant_dept", ",{$dept}_{$team}_{$user_no},");
				}
			}
		}

		$query = $this->db->get('mgr_menu_new');

		$menu_no =  $query->result_array();
		return $menu_no;
	}

	public function update_menu($record, $menu_no) {
		$this->db->where('no',$menu_no);
		$rs = $this->db->update('mgr_menu_new',$record);
		return $rs;

	}

	function select_team_paging($where, $offset=0, $limit=15) {
		$count_total = $this->db->count_all_results('team_info');
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('team_code','DESC');

		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		// $this->db->from($this->tbl['log'].' AS logs');
		// $this->db->join('user_info AS user', 'user.user_id = logs.user_id');

		$rs = $this->db->get('team_info');
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


	function select_group_paging($where, $offset=0, $limit=15) {
		$count_total = $this->db->count_all_results('mgr_auth_group');
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');

		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		// $this->db->from($this->tbl['log'].' AS logs');
		// $this->db->join('user_info AS user', 'user.user_id = logs.user_id');

		$rs = $this->db->get('mgr_auth_group');
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

	function select_group_row($where) {
		$this->db->where($where);
		$query = $this->db->get('mgr_auth_group');
		return $query->row_array();
	}

	/**
	 * 관리 그룹 counting
	 * @param  array $where 검색조건
	 * @return integer 검색된 레코드수
	 */
	function count_group($where) {
		$this->db->where($where);
		$count = $this->db->count_all_results('mgr_auth_group');
		return $count;
	}

	function insert_group($record) {
		$rs = $this->db->insert('mgr_auth_group',$record);
		return $rs;
	}

	function update_group($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('mgr_auth_group',$record);
		return $rs;
	}

	function delete_group($where) {
		if(!$where) return false;
		$this->common_lib->set_where($where);
		$rs = $this->db->delete('mgr_auth_group');
		return $rs;
	}


	function count_team($where) {
		$this->db->where($where);
		$count = $this->db->count_all_results('team_info');
		return $count;
	}

	/**
	 * 팀 추가
	 * @param  array $record 입력데이터
	 * @return boolean         [description]
	 */
	function insert_team($record) {
		$rs = $this->db->insert('team_info',$record);
		return $rs;
	}

	/**
	 * 팀정보 변경
	 * @param  array $record 수정데이터
	 * @param  array $where  조건
	 * @return boolean 결과
	 */
	function update_team($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('team_info',$record);
		return $rs;
	}

	function select_max($where, $field, $tbl="code_item") {
		$this->db->select_max($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get($tbl);
		return $query->row_array();
	}
}
