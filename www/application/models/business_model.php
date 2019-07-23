<?php
/**
 * 작성 : 2014.12.09
 * 수정 :
 *
 * @author 우석진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Business_model extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

	function select_settle_paging($where, $offset=0, $limit=15, $where_offset = array()) {
		
		$this->db->where($where_offset);
		$this->db->from('settle AS s');
		$this->db->join('settle_draft AS sd', 'sd.settle_no = s.no', 'LEFT');
		$this->db->join('user_info AS u', 's.user_no = s.no', 'LEFT');
		$this->db->join('settle_person AS p', 'p.settle_no = s.no', 'LEFT');
		//$this->db->join('settle_expense AS e', 'e.settle_no = s.no', 'LEFT');

		$count_total = $this->db->count_all_results();
		
		$where = array_merge($where_offset, $where);

		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('s.no','DESC');
		$this->db->group_by('s.no');

		$this->db->select('SQL_CALC_FOUND_ROWS 
			s.no, s.user_no, s.settle_number, s.settle_type, s.title, s.content, s.status, s.biz_id,
        	date_format(s.reg_date,"%Y-%m-%d") as reg_date, date_format(s.mod_date,"%Y-%m-%d") as mod_date, u.name,
        	(SELECT u.name FROM settle_person as sp LEFT JOIN user_info AS u ON sp.user_no = u.no
        		WHERE sp.person_type =0 AND ( sp.status = 0 OR sp.status = 1 )  AND sp.settle_no = s.no ORDER BY level asc limit 1 ) approval_now,
        	(SELECT u.name FROM settle_person as sp LEFT JOIN user_info AS u ON sp.user_no = u.no
        		WHERE sp.person_type =0 AND sp.settle_no = s.no ORDER BY level desc limit 1 ) approval_last', FALSE);
		$this->db->from('settle AS s');
		$this->db->join('settle_draft AS sd', 'sd.settle_no = s.no', 'LEFT');
		$this->db->join('user_info AS u', 's.user_no = u.no', 'LEFT');
		$this->db->join('settle_person AS p', 'p.settle_no = s.no', 'LEFT');
		//$this->db->join('settle_expense AS e', 'e.settle_no = s.no', 'LEFT');

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
		
		
		
		
		/*
		$this->common_lib->set_where($where_offset);
		$count_total = $this->db->count_all_results('settle AS s');


		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}


		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$this->db->from('settle AS s');
		// $this->db->join('settle_draft', 'settle_draft.settle_no = s.no');
		// $this->db->join('user_info', 'user_info.no = s.user_no');
		$this->db->join('settle_person AS p', 'p.settle_no = s.no');
		// $this->db->join('settle_expense', 'user_info.no = s.user_no');
		$this->db->order_by('s.no DESC');

		$rs = $this->db->get();

		echo $this->db->last_query();
		//
		// $this->db->where($where_default);
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
		*/
	}



	function set_settle_status( $settle_no, $user_no ){
	    // array( "settle_no" => $settle_no, "user_no" => $user_no, "status" => 0 )
	    $sql = "
		UPDATE
            settle_person as p
            LEFT JOIN settle as s ON p.settle_no = s.no
            SET s.status = 1
        WHERE
	        p.settle_no = '".$settle_no."' and ( p.person_type = 0 AND p.status = 0 ) AND p.user_no = '".$user_no."'
        ";

	    $query = $this->db->query($sql);

	    $this->db->update("settle_person", array( 'status' => '1' ) , "settle_no = ".$settle_no." AND user_no = ".$user_no." AND ( ( person_type = 0 AND status = 0 ) OR ( person_type = 1 AND (status IS NULL OR status = 0 ) ) ) " );
	}


	function get_settle($where, $field='*', $key='', $type='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('settle');
		// echo $this->db->last_query();
		if($type=='row') {
			return $query->row_array();
		}
		else {
			return $query->result_array();
		}

	}

	function get_settle_view( $settle_no ) {

	    $sql = "
		SELECT
        	s.no, s.user_no, s.settle_number, s.settle_type, s.title, s.content, s.status, u.name,
        	s.reg_date, s.biz_id,
	        (select biz_name from biz_info as b where b.hst_code = u.hst_code limit 1 ) as biz_name,
        	(select dept_name from dept_info as d where d.dept_code = u.dept_code limit 1 ) as dept_name,
        	(select team_name from team_info as d where d.team_code = u.team_code limit 1 ) as team_name,
        	(select title from position_duty_info as pdi where type='P' AND u.position_code = pdi.code ) as position_title,
			tmp_yn,
			expense_schedule, expense_complete
        FROM
            settle AS s
	        LEFT JOIN user_info AS u ON s.user_no = u.no
	    WHERE s.no = ".$settle_no."
        ";

	    //$this->func_lib->pr($sql); exit;

	    $query = $this->db->query($sql);
	    $res = $query->row_array();

	    return $res;
	}

	function get_settle_draft( $settle_no ) {

	    $this->db->select( 'sd.no, sd.draft_no, (select title from settle as ss where ss.no = sd.draft_no ) as draft_title,
	        (select settle_number from settle as ss where ss.no = sd.draft_no ) as draft_number,
	        (select settle_type from settle as ss where ss.no = sd.draft_no ) as draft_type' );
	    $this->db->where( 'settle_no', $settle_no );
	    $query = $this->db->get( 'settle_draft as sd' );

	    $res = $query->result_array();
	    return $res;
	}

	function get_settle_person( $settle_no, $person_type ) {

	    $sql = "
		select
        	p.status , p.level , p.user_no, p.mod_date , u.name,
        	(select title from position_duty_info as pdi where type='P' AND u.position_code = pdi.code ) as position_title
        	from
        	settle_person as p
        	LEFT JOIN user_info AS u ON p.user_no = u.no
        	where p.settle_no = ".$settle_no." and p.person_type = ".$person_type."
        	order by  p.level
        ";

	    //$this->func_lib->pr($sql); exit;

	    $query = $this->db->query($sql);
	    $res = $query->result_array();
	    return $res;
	}

	function get_settle_file( $settle_no ) {

	    $this->db->select( 'file_name, ori_name, no' );
	    $this->db->where( 'settle_no', $settle_no );
	    $query = $this->db->get( 'settle_file' );

	    $res = $query->result_array();
	    return $res;
	}

    function get_settle_expense( $where ) {
		$this->db->select( "s.*" );
		$this->common_lib->set_where($where);
		$query = $this->db->get( 'settle_expense as s' );

		$res = $query->result_array();
	    return $res;
	}

/*
	function select_settle_list_paging($where, $offset=0, $limit=15, $where_offset = array()) {
		$this->db->where($where_offset);
		$this->db->from('settle');
		$count_total = $this->db->count_all_results();

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *'); //, IF(LENGTH(l.tel) > 0, (select COUNT(distinct(biz_id)) from consulting_info where biz_id!=l.biz_id and tel=l.tel ), 0) as other_cnt

		$this->db->from('settle');
		$rs = $this->db->get();
		$list = $rs->result_array();

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;

		$this->db->from('settle');
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
*/
	
	/*
	function get_settle_list( $first = 0, $limit = 20, $where = null, $order_by='' ) {

	    $sql = "
		SELECT
        	s.no, s.user_no, s.settle_number, s.settle_type, s.title, s.content, s.status, s.biz_id,
        	date_format(s.reg_date,'%Y-%m-%d') as reg_date, date_format(s.mod_date,'%Y-%m-%d') as mod_date, u.name,
        	(SELECT u.name FROM settle_person as sp LEFT JOIN user_info AS u ON sp.user_no = u.no
        		WHERE sp.person_type =0 AND ( sp.status = 0 OR sp.status = 1 )  AND sp.settle_no = s.no ORDER BY level asc limit 1 ) approval_now,
        	(SELECT u.name FROM settle_person as sp LEFT JOIN user_info AS u ON sp.user_no = u.no
        		WHERE sp.person_type =0 AND sp.settle_no = s.no ORDER BY level desc limit 1 ) approval_last
        FROM
            settle AS s
        	LEFT JOIN settle_draft AS sd ON s.no = sd.settle_no
        	LEFT JOIN user_info AS u ON s.user_no = u.no
	        LEFT JOIN settle_person AS p ON s.no = p.settle_no
	        LEFT JOIN settle_expense AS e ON s.no = e.settle_no
	    ".$where." GROUP BY s.no
	    ".$order_by."
	    LIMIT ".$first.", ".$limit."
        ";

	    //$this->func_lib->pr($sql); exit;

	    $query = $this->db->query($sql);
	    $res = $query->result();

	    return $res;
	}
	*/

	function get_settle_total( $where ) {

	    // 20170220 kruddo : 근태신청 검색 오류 수정, 페이지 갯수 오류 수정
		/*
		$sql = "
		SELECT
        	count(*) as cnt
        FROM
            settle AS s
        ".$where."
        ";
		*/

		$sql = "
		SELECT
        	count(distinct s.no) as cnt
        FROM
            settle AS s
			LEFT JOIN settle_draft AS sd ON s.no = sd.settle_no
        	LEFT JOIN user_info AS u ON s.user_no = u.no
	        LEFT JOIN settle_person AS p ON s.no = p.settle_no
	        LEFT JOIN settle_expense AS e ON s.no = e.settle_no

        ".$where."
        ";
		// 20170220 kruddo : 근태신청 검색 오류 수정, 페이지 갯수 오류 수정


	    //$this->func_lib->pr($sql); exit;

	    $query = $this->db->query($sql);
		$data = $query->row();

		return $data->cnt;
	}

	function get_settle_total_give( $where ) {
		// 20170220 kruddo : 페이지 갯수 오류 수정
	    /*
		$sql = "
		SELECT
        	count(*) as cnt
        FROM
            settle AS s
	        LEFT JOIN user_info AS u ON s.user_no = u.no
	        LEFT JOIN settle_person AS p ON s.no = p.settle_no
	        LEFT JOIN settle_expense AS e ON s.no = e.settle_no
        ".$where."
        ";
		*/
		$sql = "
		SELECT
        	count(distinct s.no) as cnt
        FROM
            settle AS s
	        LEFT JOIN user_info AS u ON s.user_no = u.no
	        LEFT JOIN settle_person AS p ON s.no = p.settle_no
	        LEFT JOIN settle_expense AS e ON s.no = e.settle_no
        ".$where."
        ";
		// 20170220 kruddo : 페이지 갯수 오류 수정

	    //$this->func_lib->pr($sql); exit;

	    $query = $this->db->query($sql);
	    $data = $query->row();

	    return $data->cnt;
	}

	function get_settle_num_cnt( $settle_num_title ){
	    $sql = "
		SELECT
        	count(*) as cnt
        FROM
            settle
        WHERE
	        settle_number like '".$settle_num_title."%'
        ";

	    $query = $this->db->query($sql);
	    $data = $query->row();

	    return $data->cnt;
	}


	// 사업장 정보
	function get_biz_list(){

	    $this->db->select( '*' );
	    $this->db->where('use_flag','Y');
	    $query = $this->db->get( 'biz_info' );
	    $result = $query->result_array('biz_id');
	    return $result;

	}

	
	// 팀정보
	function get_team_list($dept_code = '') {
	    $this->db->select( '*' );

	    if (! empty( $dept_code )) $this->db->where( 'dept_code', $dept_code );
	    $this->db->where( 'status', '1' );
	    $this->db->order_by( 'team_name', 'ASC' );
	    $query = $this->db->get( 'team_info' );

	    $result = $query->result_array();

	    foreach ( $result as $i => $row ) {
	        $list[$row['team_code']] = $row['team_name'];
	    }
	    return $list;
	}

	function get_team_list_insert() {
	    $this->db->select( '*' );

		$names = array('90','60');
		$this->db->or_where_in('dept_code', $names);

	    $this->db->where( 'status', '1' );
	    $this->db->order_by( 'team_name', 'ASC' );
	    $query = $this->db->get( 'team_info' );

	    $result = $query->result_array();

	    foreach ( $result as $i => $row ) {
	        $list[$row['team_code']] = $row['team_name'];
	    }
	    return $list;
	}

	// 부서 정보
	function get_dept_list($hst_code = '') {
	    $this->db->select( '*' );

	    if (! empty( $hst_code )) $this->db->where( 'hst_code', $hst_code );
	    $this->db->where( 'status', '1' );
	    $this->db->order_by( 'order_no', 'ASC' );
	    $query = $this->db->get( 'dept_info' );

	    $result = $query->result_array();

	    foreach ( $result as $i => $row ) {
	        $list[$row['dept_code']] = $row['dept_name'];
	    }
	    return $list;
	}

    function get_user_info_list( $what, $code ){

	    $this->db->select( "u.no, u.name,
			(select title from position_duty_info as pdi where type='P' AND u.position_code = pdi.code ) as position_title,
			(select title from position_duty_info as pdi where type='D' AND u.duty_code = pdi.code ) as duty_title" );

	    if( $what == "hst" ){
	       if (! empty( $code )) $this->db->where( 'hst_code', $code );
	    }

	    if( $what == "dept" ){
	        if (! empty( $code )) $this->db->where( 'dept_code', $code );
	    }

	    if( $what == "team" || $what == "team_s"){
	        if (! empty( $code )) $this->db->where( 'team_code', $code );
	    }

	    if( $what == "search" ){
	        if (! empty( $code )) $this->db->like( 'name', $code );
	    }

	    if( $what == "save_line" ){
	        if (! empty( $code )){
	            $save_line = explode("-", $code);

	            // $save_line_in = "'".str_replace("-", "','", $code)."'";
	            $save_line_in = str_replace("-", ",", $code);
	            $this->db->where_in( 'u.no', $save_line );
	            $this->db->_protect_identifiers = FALSE;
	            // $this->db->order_by( 'FIELD ( u.no , '.$save_line_in.')' );
	            $this->db->_protect_identifiers = TRUE;
	        }
	    }
	    //echo $code;

	    $this->db->where( 'u.status', '1' );
	    //$this->db->order_by( 'u.duty_code', 'DESC' );
	    $query = $this->db->get( 'user_info as u' );

	    $result = $query->result_array();
		// pre($result);
	    foreach ( $result as $i => $row ) {
	        if( $what == "team_s"){
	           $list[$row['no']] = $row['name'];
	        }
	        else{
	           $list[$row['no']] = $row['name']."(".trim($row['position_title'])."/".trim($row['duty_title']).")";
	        }
	    }

	    if( $what == "save_line" ){
		    $line = array();
		    foreach($save_line as $row) {
		    	if(empty($list[$row])) continue;
		    	$line[$row] = $list[$row];
		    }
		    return $line;
		}
		else {
			return $list;
		}

	}


	public function get_save_line_list($input) {
	    $this->db->select( "*" );

	    $this->db->where( 'user_no', $this->session->userdata( 'ss_user_no' ) );
	    $query = $this->db->get( 'settle_save_line' );

	    $result = $query->result_array();
	    return $result;
	}

	// 기안 저장
	public function settle_insert($input) {
	    $this->db->insert( 'settle', $input );
	    return $this->db->insert_id();
	}

	public function settle_modify($input, $settle_no) {
	    $this->db->update("settle", $input , array( "no" => $settle_no ) );
	}

	public function settle_reset( $settle_no ){
	    $this->db->delete("settle_person", array( "settle_no" => $settle_no ) );
	    $this->db->delete("settle_draft", array( "settle_no" => $settle_no ) );
	    $this->db->delete("settle_expense", array( "settle_no" => $settle_no ) );
	}

	public function settle_file_del( $file_cancel_no ){
	    $sql = "
		DELETE FROM settle_file
        WHERE
	        no in (".$file_cancel_no.")
        ";

	    $query = $this->db->query($sql);
	}

	// 기안 파일
	public function settle_file_insert($input) {
	    $this->db->insert( 'settle_file', $input );
	}

	// 결재 참조라인 저장
	public function settle_person_insert($input) {
	    $this->db->insert( 'settle_person', $input );
	}

	// 기안 참조 저장
	public function settle_draft_insert($input) {
	    $this->db->insert( 'settle_draft', $input );
	}

	// 지출/주간입출금/근태
	public function settle_expense_insert($input) {
	    $this->db->insert( 'settle_expense', $input );
	}

	// 상담 실장
	public function get_advice_person(){

	    $this->db->select( "no, name " );

	    //$this->db->where( 'dept_code', 90 );
	    $this->db->where( 'status', '1' );
	    $query = $this->db->get( 'user_info' );

	    $result = $query->result_array();

	    return $result;

	}

    // 상담 실장
	public function get_settle_consult($settle_no){

	    $this->db->select( "sc.*, (select name from user_info as u WHERE sc.user_no = u.no ) as name" );
	    $this->db->where( 'sc.settle_no', $settle_no );
	    $query = $this->db->get( 'settle_consult as sc' );

	    $result = $query->result_array();

	    return $result;
	}

	// 기안 취소
	public function settle_cancel($settle_no){
	    $this->db->update("settle", array( 'status' => '4', 'mod_date' => date('Y-m-d H:i:s') ) , "no = ".$settle_no);
	}

	// 기안 삭제
	public function settle_del($settle_no, $mode=''){
		if($mode == 'termindate') {
			$this->db->update("settle", array( 'status' => '5' ) , "no = ".$settle_no);
		}
		else {
			//결제참고메모 삭제(settle_consult)
			$this->db->delete("settle_consult", array( "settle_no" => $settle_no ) );
			//참조기안(settle_draft)
			$this->db->delete("settle_draft", array( "draft_no" => $settle_no ) );
			//지출/주간입금/근태(settle_expense)
			$this->db->delete("settle_expense", array( "settle_no" => $settle_no ) );
			//첨부파일(settle_file)
			$this->db->delete("settle_file", array( "settle_no" => $settle_no ) );
			//결제라인정보 삭제(settle_person)
			$this->db->delete("settle_person", array( "settle_no" => $settle_no ) );
			//문서삭제(settle)
			$this->db->delete("settle", array( "no" => $settle_no ) );
		}

	}

    // 참고 내용추가하기
	public function settle_consult_insert($input) {
	    $this->db->insert( 'settle_consult', $input );
	}

	// 결재라인 저장
	public function save_line_add($input) {
		$this->db->insert( 'settle_save_line', $input );
	}

	/**
	 * 결제라인삭제
	 * @param  int $line_no 결제라인key
	 * @return boolean 처리성공여부
	 */
	public function save_line_del($line_no) {
		$rs = $this->db->delete("settle_save_line", array( "no" => $line_no ) );
		return $rs;
	}

	// 기안 승인 및 완료
	public function settle_approve_end( $settle_no, $max_level ) {
		$user_no = $this->session->userdata( 'ss_user_no' );
	    $this->db->update("settle_person", array( 'status' => '2', 'mod_date' => date('Y-m-d H:i:s') , 'real_user_no'=>$user_no) , array( "settle_no" => $settle_no, "level" => $max_level ) );
	    $this->db->update("settle", array( 'status' => '2', 'mod_date' => date('Y-m-d H:i:s') ) , array( "no" => $settle_no ) );
	}

	// 기안 승인
    public function settle_approve_next( $settle_no, $max_level, $my_level ) {
    	$user_no = $this->session->userdata( 'ss_user_no' );
        $this->db->update("settle_person", array('status' => '2', 'mod_date' => date('Y-m-d H:i:s'), 'real_user_no'=>$user_no) , array( "settle_no" => $settle_no, "level" => $my_level ) );
        $this->db->update("settle_person", array( 'status' => '0' ) , array( "settle_no" => $settle_no, "level" => $my_level+1 ) );
        // 승인후 다음사람다시 대기 상태로
        $this->db->update("settle", array( 'status' => '0' ) , array( "no" => $settle_no ) );
	}

	// 기안 반려
    public function settle_return( $settle_no, $user_no ) {
	    $this->db->update("settle_person", array( 'status' => '3', 'mod_date' => date('Y-m-d H:i:s') ) , array( "settle_no" => $settle_no, "user_no" => $user_no ) );
	    $this->db->update("settle", array( 'status' => '3', 'mod_date' => date('Y-m-d H:i:s') ) , array( "no" => $settle_no ) );
	}

	// 파일 원본 이름
    public function get_ori_name( $file_name ){
        $sql = "
		SELECT
        	ori_name
        FROM
            settle_file AS s
        WHERE file_name = '".$file_name."'
        ";

        $query = $this->db->query($sql);
        $data = $query->row();

        return $data->ori_name;
    }

    public function select_person_instead($where, $field='*') {
    	$this->db->select('GROUP_CONCAT(user_no) AS users');
		$this->common_lib->set_where($where);
		$query = $this->db->get("settle_person_instead");
		$rs = $query->row_array();
		return $rs;//['users'];
	}







	

	var $table = '';

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


	public function update($pk_code, $pk_value, $input) {
		$this->db->where( $pk_code, $pk_value );
		$this->db->update( $this->table, $input );
	}



	public function delete($pk_code, $pk_value) {
		$this->db->where( $pk_code, $pk_value );
		$this->db->delete( $this->table );
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
		$this->db->from('settle AS s');

		$count_total = $this->db->count_all_results();
		
		$where = array_merge($where_offset, $where);

		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS s.*', FALSE);
		$this->db->from('settle AS s');

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


	// 20170123 kruddo : 올린결재 > 임시 저장 목록
	function get_settle_tmp_list(){

		$user_no = $this->session->userdata( 'ss_user_no' );
		
	    $this->db->select( '*' );
	    $this->db->where('tmp_yn','Y');
		$this->db->where('user_no',$user_no);

	    $query = $this->db->get( 'settle' );
	    $result = $query->result_array('settle_tmp_list');
	    return $result;

	    $query = $this->db->query($sql);
	    $res = $query->result();

	    return $res;

	}

	// 임시저장 파일 저장
	function settle_tmp_file_insert($settle_no, $tmp_file){
		$result = explode(",", $tmp_file);
		for($i=0; $i<count($result); $i++){
			$this->db->select('file_name, ori_name');
			$this->db->from('settle_file');
			$this->db->where('no', $result[$i]);
			$query = $this->db->get();

			if($query->num_rows()) {
				$new_author = $query->result_array();

				foreach ($new_author as $row => $author) {
					$input = array(
							'settle_no' => $settle_no,
							'file_name' => $author['file_name'],
							'ori_name' => $author['ori_name'],
					);

					$this->db->insert("settle_file", $input);
				}           
			}
		}
		
		/*
		$sql = "INSERT INTO settle_file(settle_no, file_name, ori_name)
				SELECT ".$settle_no.", file_name, ori_name 
					FROM settle_file 
				WHERE no IN ('".$tmp_file."')";


        $query = $this->db->query($sql);
        $data = $query->row();
		*/

	}
	// 20170123 kruddo : 올린결재 > 임시 저장 목록

}
