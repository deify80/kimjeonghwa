<?php
/**
 * 작성 : 2014.10.17
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Consulting_model extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

	public function get_db_list($first = 0, $limit = 0, $where = null, $order_by="db_seqno DESC") {
		$this->db->select( "SQL_CALC_FOUND_ROWS *, (IF(LENGTH(db_info.tel) > 0,(select distinct(biz_id) from consulting_info where biz_id!=db_info.biz_id and tel=db_info.tel ),'')) as other_biz_id", false );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		if (is_array( $where )) $this->common_lib->set_where( $where, false );
		$this->db->order_by($order_by);
		$query = $this->db->get( 'db_info', $limit, $first );

		$result = $query->result_array();
		return $result;
	}

	public function count_db($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('db_info');
		return $count;
	}

	function select_db($where, $field='*'){
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get( 'db_info');
		return $query->result_array();
	}

	function select_db_row($where, $field='*'){
		$this->db->order_by('db_seqno DESC');
		$arr = $this->select_db($where, $field);
		return array_shift($arr);
	}

	function select_db_paging($where, $offset=0, $limit=15, $where_offset = array()) {
		$this->db->where($where_offset);
		$this->db->from('db_info AS db');
		$this->db->join('consulting_info AS c', 'c.db_seqno=db.db_seqno', 'left');
		$count_total = $this->db->count_all_results();

		//echo $this->db->last_query();

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('db.db_seqno','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS db.*, c.team_code, c.cst_status, c.cst_seqno'); //, IF(LENGTH(l.tel) > 0, (select COUNT(distinct(biz_id)) from consulting_info where biz_id!=l.biz_id and tel=l.tel ), 0) as other_cnt

		$this->db->from('db_info AS db');
		$this->db->join('consulting_info AS c', 'c.db_seqno=db.db_seqno' ,'left');
		$rs = $this->db->get();
		$list = $rs->result_array();

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;

		$this->common_lib->set_where(array_merge($where, array('LEFT(db.reg_date, 8) = '=>date('Ymd'), 'db_status'=>'1')));
		$this->db->from('db_info AS db');
		$this->db->join('consulting_info AS c', 'c.db_seqno=db.db_seqno', 'left');
		$count_today = $this->db->count_all_results();


		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search,
				'today'=>$count_today
			),
			'list'=>$list
		);
		return $return;
	}

	function select_consulting_paging($where, $offset=0, $limit=15, $where_offset = array(), $order_by='cst_seqno DESC') {
		$this->common_lib->set_where($where_offset);
		$this->db->from('consulting_info AS c');
		$this->db->join('patient AS p', 'c.cst_seqno=p.cst_seqno', 'LEFT');
		$count_total = $this->db->count_all_results();


		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}


		$this->db->select('SQL_CALC_FOUND_ROWS c.*, IF(ISNULL(c.accept_date), 1, 0) AS accept_flag, p.is_o', FALSE);
		$order_by = ($order_by)?$order_by:'c.cst_seqno DESC';
		$this->db->order_by($order_by);


		$this->db->from('consulting_info AS c');
		$this->db->join('patient AS p', 'c.cst_seqno=p.cst_seqno', 'LEFT');
		$rs = $this->db->get();
		//echo $this->db->last_query();
		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;

		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search,
				'today'=>$count_today
			),
			'list'=>$rs->result_array()
		);
		return $return;
	}


	function select_consulting_row($where, $tbl='consulting_info', $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get($tbl);
		return $query->row_array();
	}

	function select_consulting_all($where, $tbl='consulting_info', $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get($tbl);
		return $query->result_array();
	}

	function select_work_paging($where, $offset=0, $limit=15, $where_offset = array()) {
		$this->db->where($where_offset);
		$this->db->from('log_change AS l');
		$this->db->join('consulting_info AS c', 'c.cst_seqno=l.cst_seqno');
		$count_total = $this->db->count_all_results();


		$where = array_merge($where, $where_offset);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}


		$this->db->select('SQL_CALC_FOUND_ROWS l.*, GROUP_CONCAT(CONCAT(title, " : ", contents) SEPARATOR  "<br />") AS contents, c.name AS client_name, c.cst_status,  LEFT(l.reg_date, 8) AS date, COUNT(*) AS contents_cnt ', FALSE);
		$order_by = ($order_by)?$order_by:'l.reg_date DESC';
		$this->db->order_by($order_by);
		$this->db->group_by('date, l.cst_seqno', false);
		$this->db->from('log_change AS l');
		$this->db->join('consulting_info AS c', 'c.cst_seqno=l.cst_seqno');
		$rs = $this->db->get();


		// echo $this->db->last_query();
		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;


		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search,
				'today'=>$count_today
			),
			'list'=>$rs->result_array()
		);
		return $return;
	}


	public function get_db_in($db_seqno) {
		$this->db->where_in( 'db_seqno', $db_seqno );
		$this->db->order_by( 'db_seqno', 'ASC' );
		$query = $this->db->get( 'db_info' );

		$result = $query->result_array();
		return $result;
	}



	public function get_total() {
		$result = $this->db->query( 'SELECT FOUND_ROWS() as total' );
		$total = $result->row( 0 )->total;
		return $total;
	}



	public function db_insert($input) {
		$rs = $this->db->insert( 'db_info', $input );
		if($rs) {
			return $this->db->insert_id();
		}
		else {
			return false;
		}
	}

	public function db_update($record, $where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->update('db_info', $record );
		return $rs;
	}


	public function consulting_insert($input) {
		$result = $this->db->insert( 'consulting_info', $input );
		return $result;
	}

	public function consulting_update($record, $where) {
		$this->common_lib->set_where($where);
		$result = $this->db->update('consulting_info', $record);
		return $result;
	}

	public function consulting_count($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('consulting_info');
		return $count;
	}

	public function memo_insert($input) {
		$result = $this->db->insert( 'consulting_memo', $input );
		return $result;
	}

	public function charge_log_insert($record) {
		$this->db->set('insert_date','NOW()', FALSE);
		$result = $this->db->insert( 'consulting_charge_log', $record );
		return $result;
	}

	public function charge_log_count($where) {
		$this->db->where($where);
		$this->db->from('consulting_charge_log');
		return $this->db->count_all_results();
	}

	public function charge_log_last($where) {
		$this->db->where($where);
		$this->db->order_by('seqno','desc');
		$query = $this->db->get('consulting_charge_log');
		return $query->row_array();
	}

	public function log_insert($input) {
		$result = $this->db->insert( 'log_change', $input );
	}

	public function check_tel($tel) {
		$this->db->select( 'COUNT(tel) as total, MAX(cst_seqno) AS cst_seqno, reg_date, team_code, org_team_code' );
		$this->db->where( 'tel', str_replace( '-', '', $tel ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'use_flag','Y' );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->order_by( 'cst_seqno', 'DESC' );
		$query = $this->db->get( 'consulting_info', 1 );
		$row = $query->row_array();

		return $row;
	}

	public function update_db_status($db_seqno, $assign_type, $db_status) {
		if(empty($db_seqno)) return false;

		$input = null;
		$input['assign_type'] = $assign_type;
		$input['db_status'] = $db_status;
		$input['assign_date'] = TIME_YMDHIS;
		$input['assign_user_id'] = $this->session->userdata( 'ss_user_id' );

		$this->db->where_in( 'db_seqno', $db_seqno );
		$rs = $this->db->update( 'db_info', $input );
		return $rs;
	}

	function select_consulting($where, $field='*', $order_by='', $key='', $tbl="consulting_info") {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		if($order_by) $this->db->order_by($order_by);

		$query = $this->db->get($tbl);
		return $query->result_array($key);
	}

	public function get_cst_list($first = 0, $limit = 0, $type, $where = null, $sidx, $sord) {
		$this->db->select( 'SQL_CALC_FOUND_ROWS *, if(isnull(accept_date), 1, 0) AS accept_flag,  (select count(seqno) from consulting_memo where cst_seqno=consulting_info.cst_seqno) as memo_total, (select memo from consulting_memo where cst_seqno=consulting_info.cst_seqno order by seqno desc limit 1) as last_memo', false );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'use_flag', 'Y' );
		if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') $this->db->where( 'cst_seqno > ', 9000 );

		if ($type == '') {
			if ($this->session->userdata( 'ss_dept_code' ) == '90') $this->db->where( 'team_code', $this->session->userdata( 'ss_team_code' ) );
			$this->db->where( 'charge_date > ', TIME_YMDHIS );
		} else if ($type == 'share') {
			$this->db->where( 'charge_date < ', TIME_YMDHIS );
		}

		if (is_array( $where )) $this->common_lib->set_where( $where );
		if ($this->session->userdata( 'ss_dept_code' ) == '90') $this->db->order_by( 'accept_flag', $sord );
		$this->db->order_by( $sidx, $sord );
		$query = $this->db->get( 'consulting_info', $limit, $first );
		// echo $this->db->last_query();
		$result = $query->result_array();
		return $result;
	}

	function get_cst_all($where, $field='*', $order_by='cst_seqno DESC') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->order_by($order_by);
		$query = $this->db->get('consulting_info');
		return $query->result_array();
	}



	public function get_cst_info($cst_seqno, $field = '*') {
		$this->db->select( $field, false );
		$this->db->where( 'cst_seqno', $cst_seqno );
		$query = $this->db->get( 'consulting_info' );
		$row = $query->row_array();
		return $row;
	}



	public function update_cst($cst_seqno, $input) {

		//charge 변경시 로그
		$old = $this->get_cst_info($cst_seqno, 'charge_date, biz_id, hst_code, team_code');

		$this->db->where( 'cst_seqno', $cst_seqno );
		$rs = $this->db->update( 'consulting_info', $input );

		if($rs) {
			$new = $this->get_cst_info($cst_seqno, 'charge_date, biz_id, hst_code, team_code');
			if($old['charge_date'] != $new['charge_date'] || ($old['team_code'] != $new['team_code'] && $new['charge_date'] > $now)) {
				$now = date('YmdHis');
				$status = ($old['charge_date'] < $now && $new['charge_date'] >= $now)?'move':'remain';

				$datum = array(
					'cst_seqno'=>$cst_seqno,
					'act'=>$status, //first:최초분배, move:(공동DB->팀DB)이동, remain:변화없음
					'hst_code'=>$new['hst_code'],
					'biz_id'=>$new['biz_id'],
					'team_code'=>$new['team_code'],
					'team_code_from'=>$old['team_code'],
					'charge_date'=>$new['charge_date']
				);
				$this->charge_log_insert($datum);
			}
		}

		return $rs;
	}


	public function update_consulting($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('consulting_info', $record );
		return $rs;
	}


	public function get_memo_list($cst_seqno) {
		$this->db->select( 'A.*, B.name' );
		$this->db->where( 'cst_seqno', $cst_seqno );
		$this->db->where( 'A.reg_user_id', 'B.user_id', false );
		$this->db->order_by( 'seqno', 'DESC' );
		$query = $this->db->get( 'consulting_memo as A, user_info as B' );
		$result = $query->result_array();
		return $result;
	}



	public function get_log_list($cst_seqno) {
		$this->db->select( 'A.*, B.name' );
		$this->db->where( 'cst_seqno', $cst_seqno );
		// $this->db->where( 'title !=', '상태' );
		$this->db->where( 'title !=', '상담내용' );
		//$this->db->where( 'title !=', '팀' );
		$this->db->where( 'A.reg_user_id', 'B.user_id', false );
		$this->db->order_by( 'seqno', 'DESC' );
		$query = $this->db->get( 'log_change as A, user_info as B' );
		$result = $query->result_array();
		return $result;
	}



	public function get_cst_status_list($cst_seqno) {
		$this->db->select( 'A.*, B.name' );
		$this->db->where( 'cst_seqno', $cst_seqno );
		$this->db->where( 'A.reg_user_id', 'B.user_id', false );
		$this->db->order_by( 'seqno', 'DESC' );
		$query = $this->db->get( 'log_cst_status as A, user_info as B' );
		$result = $query->result_array();
		return $result;
	}



	public function get_insert_id() {
		$id = $this->db->insert_id();
		return $id;
	}



	public function get_valid_contact($cst_seqno, $limit_date) {
		$this->db->select( 'contact_user_id' );
		$this->db->where( 'close_status !=', 'Y' );
		$this->db->where( 'contact_date >', $limit_date );
		$this->db->where( 'seqno', '(select max(seqno) from consulting_contact where `cst_seqno` =  ' . $cst_seqno . ')', false );
		$this->db->where( 'A.cst_seqno', 'B.cst_seqno', false );
		$query = $this->db->get( 'consulting_contact as A, consulting_info as B' );
		$row = $query->row_array();
		return $row['contact_user_id'];
	}



	public function my_valid_contact($limit_date) {
		$this->db->select( '*' );
		$this->db->where( 'contact_user_id', $this->session->userdata( 'ss_user_id' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( "(close_status!='Y' AND contact_date>$limit_date)" );
		$this->db->order_by( 'seqno', 'DESC' );
		$query = $this->db->get( 'consulting_contact', 1 );
		$row = $query->row_array();
		return $row;
	}



	public function contact_insert($cst_seqno) {
		$input = null;
		$input['contact_date'] = TIME_YMDHIS;
		$input['cst_seqno'] = $cst_seqno;
		$input['contact_user_id'] = $this->session->userdata( 'ss_user_id' );
		$input['team_code'] = $this->session->userdata( 'ss_team_code' );
		$input['biz_id'] = $this->session->userdata( 'ss_biz_id' );
		$input['hst_code'] = $this->session->userdata( 'ss_hst_code' );

		$this->db->insert( 'consulting_contact', $input );
	}



	public function cst_status_insert($cst_seqno, $cst_status) {
		$input = null;
		$input['reg_date'] = TIME_YMDHIS;
		$input['cst_seqno'] = $cst_seqno;
		$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
		$input['team_code'] = $this->session->userdata( 'ss_team_code' );
		$input['cst_status'] = $cst_status;

		$this->db->insert( 'log_cst_status', $input );
	}



	public function log_team_insert($cst_seqno, $team_code) {
		$input = null;
		$input['reg_date'] = TIME_YMDHIS;
		$input['cst_seqno'] = $cst_seqno;
		$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
		$input['team_code'] = $team_code;

		$this->db->insert( 'log_team', $input );
	}



	public function get_contact_info($cst_seqno) {
		$this->db->select( '*' );
		$this->db->where( 'cst_seqno', $cst_seqno );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->order_by( 'seqno', 'DESC' );
		$query = $this->db->get( 'consulting_contact', 1 );
		$row = $query->row_array();
		return $row;
	}



	public function close_contact($cst_seqno, $seqno = '') {
		$input = null;
		$input['close_status'] = 'Y';

		$this->db->where( 'cst_seqno', $cst_seqno );
		if ($seqno != '') $this->db->where( 'seqno', $seqno );
		$rs = $this->db->update( 'consulting_contact', $input );
		return $rs;
	}



	public function get_contact_list($field = '*', $where = null, $group_option = '') {
		$this->db->select( $field );
		$this->db->where( $where, '', false );
		$this->db->where( 'A.biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'A.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->group_by( $group_option );
		$this->db->order_by( 'A.seqno', 'DESC' );
		$this->db->from( 'consulting_contact as A' );
		$this->db->join( 'user_info as B', 'A.contact_user_id = B.user_id' );

		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}


	public function get_contact_log($first = 0, $limit = 0, $reg_date, $where_option = '') {
		$sql = "select SQL_CALC_FOUND_ROWS *, (select contents from log_change where (title='상태') and substring(reg_date, 1, 8)='" . $reg_date . "' and cst_seqno=C.cst_seqno order by seqno desc limit 1) as last_status,
			(select cst_status from log_cst_status where cst_seqno=C.cst_seqno and seqno <(select max(seqno) from log_cst_status where cst_seqno=C.cst_seqno) order by seqno desc limit 1) as pre_status
		from (select A.*, B.reg_user_id, (select count(seqno) from consulting_memo where cst_seqno=A.cst_seqno) as memo_total from consulting_info as A, log_change as B where substring(B.reg_date, 1, 8)='" . $reg_date . "' " . $where_option . " and biz_id='" . $this->session->userdata( 'ss_biz_id' ) . "' and A.cst_seqno=B.cst_seqno group by cst_seqno) as C order by cst_seqno desc limit $first, $limit";
		$query = $this->db->query( $sql );
		$result = $query->result_array();
		return $result;
	}

	public function get_memo_count($where, $group_by) {
		$this->db->select("U.team_code, C.reg_user_id, COUNT(DISTINCT C.cst_seqno) AS cnt", FALSE);
		$this->db->where($where);
		$this->db->group_by('U.team_code, C.reg_user_id');
		$this->db->from('consulting_memo AS C');
		$this->db->join('user_info AS U', 'C.reg_user_id=U.user_id');
		$query = $this->db->get();
		$result = $query->result_array();

		return $result;
	}

	public function get_log_count($where, $group_by) {
		$this->db->select("U.team_code, C.reg_user_id, COUNT(DISTINCT C.cst_seqno) AS cnt, LEFT(C.reg_date,8) AS date", FALSE);
		$this->db->where($where);
		$this->db->group_by('U.team_code, C.cst_seqno, date');
		$this->db->from('log_change AS C');
		$this->db->join('user_info AS U', 'C.reg_user_id=U.user_id');
		$query = $this->db->get();

		$result = $query->result_array();
		return $result;
	}



	public function get_cst_result($where) {
		if (is_array( $where )) {
			$where_option = $this->db->set_where_option( $where );
			$this->db->where( $where_option, null, false );
		}
		$this->db->select( 'consulting_info.*, team_info.team_name' );
		$this->db->order_by( 'cst_seqno', 'DESC' );
		$this->db->from( 'consulting_info' );
		$this->db->join( 'team_info', 'consulting_info.team_code = team_info.team_code' );
		$query = $this->db->get( "", 1 );
		$row = $query->row_array();
		return $row;
	}


	public function get_sales_status($where, $cst_status_list) {
		$this->db->select( 'org_team_code as team_code, cst_status, count(cst_seqno) as total' );
		$this->db->where_in( 'cst_status', $cst_status_list );
		if (is_array( $where )) $this->db->where( $where, '', false );
		// $this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'use_flag', 'Y' );
		$this->db->group_by( 'cst_status, org_team_code' );
		$this->db->order_by( 'cst_status, count(cst_seqno)', 'DESC' );
		$query = $this->db->get( 'consulting_info' );

		$result = $query->result_array();

		return $result;
	}



	public function get_sales_total($where, $team_field='org_team_code') {
		$this->db->select( 'A.'.$team_field.' as team_code, count(cst_seqno) as total, team_name' );
		if (is_array( $where )) $this->common_lib->set_where( $where );
		// $this->db->where( 'A.biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'A.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'A.use_flag', 'Y' );
		$this->db->group_by( 'A.'.$team_field );
		$this->db->order_by( 'B.order_no','ASC' ); //add 2015-05-19
		$this->db->from( 'consulting_info as A' );
		$this->db->join( 'team_info as B', 'A.'.$team_field.' = B.team_code' );
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}



	function get_cst_total($where, $type='my') {
		$this->db->select( 'COUNT(cst_seqno) as total' );
		if ($this->session->userdata( 'ss_dept_code' ) == '90') $this->db->where( 'team_code', $this->session->userdata( 'ss_team_code' ) );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'use_flag', 'Y' );
		if($type=='share') {
			$this->db->where( 'charge_date < ', TIME_YMDHIS );
		}
		else {
			$this->db->where( 'charge_date >= ', TIME_YMDHIS );
		}


		if (is_array( $where )) {
			$where_option = $this->db->set_where_option( $where );
			$this->db->where( $where_option, null, false );
		}

		$query = $this->db->get( 'consulting_info' );
		// echo $this->db->last_query()."<br /><br />";

		$row = $query->row();
		$this->total = $row->total;

		return $row->total;
	}

	function get_cst_in($where) {
		$this->db->select( '*' );

		if (is_array( $where )) {
			$where_option = $this->db->set_where_option( $where );
			$this->db->where( $where_option, null, false );
		}
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );

		$this->db->order_by( 'cst_seqno', 'ASC' );
		$query = $this->db->get( 'consulting_info' );
		$result = $query->result_array();
		return $result;
	}

	function get_neighbor($where, $order_field = 'cst_seqno', $order_option = 'ASC') {
		$this->db->select( 'cst_seqno' );

		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'use_flag', 'Y' );
		$this->db->where( 'charge_date > ', TIME_YMDHIS );
		if ($this->session->userdata( 'ss_dept_code' ) == '90') $this->db->where( 'team_code', $this->session->userdata( 'ss_team_code' ) );
		if (is_array( $where )) $this->db->where( $where );
		$this->db->order_by( $order_field, $order_option );
		$query = $this->db->get( 'consulting_info', 1 );
		$row = $query->row_array();
		return $row;
	}

	function get_permanent_cst($where) {
		$this->db->select( 'SUBSTRING(reg_date, 1, 6) as date, count(cst_seqno) as total', false );
		$this->db->where( 'team_code', $this->session->userdata( 'ss_team_code' ) );
		$this->db->where( 'permanent_status', 'Y' );
		$this->db->where( 'use_flag', 'Y' );
		$this->db->where( 'cst_status !=', '99');
		$this->db->where( 'charge_date > ', TIME_YMDHIS );
		$this->db->where( 'hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->group_by( 'date' );
		if (is_array( $where )) {
			$where_option = $this->db->set_where_option( $where );
			$this->db->where( $where_option, null, false );
		}
		$query = $this->db->get( 'consulting_info' );
		$result = $query->result_array();
		return $result;
	}



	function update_appointment_cnt($cst_seqno) {
		$this->db->set( 'appointment_cnt', 'appointment_cnt + 1', false );
		$this->db->where( 'cst_seqno', $cst_seqno );
		$this->db->update( 'consulting_info' );
	}

	function select_landing_paging($where, $offset=0, $limit=15) {
		$this->dbcn =  $this->load->database('cn', TRUE);
		$count_total = $this->dbcn->count_all_results('md21_landing_config');
		$this->dbcn->where($where);

		if(!is_null($offset)) {
			if($limit) $this->dbcn->limit($limit, $offset);
		}

		$this->dbcn->order_by('no','DESC');
		$this->dbcn->select('SQL_CALC_FOUND_ROWS *', FALSE);

		$rs = $this->dbcn->get('md21_landing_config');
		$rs_count = $this->dbcn->query('SELECT FOUND_ROWS() AS `Count`');
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

	function select_landing_row($where, $field='*') {
		$this->dbcn =  $this->load->database('cn', TRUE);
		$this->dbcn->select($field);
		$this->dbcn->where($where);
		$query = $this->dbcn->get('md21_landing_config');
		return $query->row_array();
	}

	function insert_landing($record) {
		$this->dbcn =  $this->load->database('cn', TRUE);
		$rs = $this->dbcn->insert('md21_landing_config',$record);
		return $rs;
	}

	function update_landing($record, $where) {
		$this->dbcn =  $this->load->database('cn', TRUE);
		$this->dbcn->where($where);
		$rs = $this->dbcn->update('md21_landing_config',$record);
		return $rs;
	}

	function count($where, $tbl="db_info") {
		$this->common_lib->set_where($where);
		$cnt = $this->db->count_all_results($tbl);
		return $cnt;
	}

	function groupby_consulting($where, $group_by='') {
		$this->common_lib->set_where($where);
		$this->db->select("{$group_by}, COUNT(*) AS cnt ",FALSE);
		$this->db->group_by($group_by);
		$query = $this->db->get('consulting_info');
		return $query->result_array($group_by);
	}




	// 20170208 kruddo : DB실적->상담팀실적으로 수정
	public function get_consulting_team_total($where, $team_field='org_team_code') {
		$this->db->select( 'A.'.$team_field.' as team_code, count(cst_seqno) as total, team_name' );
		if (is_array( $where )) $this->common_lib->set_where( $where );
		// $this->db->where( 'A.biz_id', $this->session->userdata( 'ss_biz_id' ) );
		$this->db->where( 'A.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'A.use_flag', 'Y' );
		$this->db->group_by( 'A.'.$team_field );
		$this->db->order_by( 'B.order_no','ASC' ); //add 2015-05-19
		$this->db->from( 'consulting_info as A' );
		$this->db->join( 'team_info as B', 'A.'.$team_field.' = B.team_code' );
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}
}
