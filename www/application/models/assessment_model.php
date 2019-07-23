<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Assessment_model extends CI_Model {
	var $table = "";


	public function __construct() {
		parent::__construct();
	}

	// 기안 파일
	public function assessment_file_insert($input) {
	    $this->db->insert( 'assessment_file', $input );
	}

	public function assessment_file_del( $file_cancel_no ){
	    $sql = "
		DELETE FROM assessment_file
        WHERE
	        no in (".$file_cancel_no.")
        ";
	     
	    $query = $this->db->query($sql);
	}

	function get_assessment_file( $settle_no ) {
	
	    $this->db->select( 'file_name, ori_name, no' );	
	    $this->db->where( 'settle_no', $settle_no );
	    $query = $this->db->get( 'assessment_file' );
	
	    $res = $query->result_array();
	    return $res;
	}
	// 파일 원본 이름
    public function get_ori_name( $file_name ){
        $sql = "
		SELECT
        	ori_name
        FROM
            assessment_file AS s
        WHERE file_name = '".$file_name."'
        ";
         
        $query = $this->db->query($sql);
        $data = $query->row();
        
        return $data->ori_name;
    }
	

	public function settion_insert($input) {
		$this->db->insert( "assessment_settion", $input );
		return $this->db->insert_id() ;		
	}

	function settion_update($input, $seqno) {
		$this->db->update("assessment_settion", $input, array('no' => $seqno));
	}	

	public function down_insert($input) {
		$this->db->insert( "assessment_down", $input );
		return $this->db->insert_id() ;
	}

	public function result_insert($input) {
		$this->db->insert( "assessment_result", $input );
		return $this->db->insert_id() ;
	}
	
	function deletes($where) {
			
		if (is_array($where)) {
			$where_option = $this->db->set_where_option($where);
			$this->db->where($where_option, null, false);
		}
		$this->db->delete("assessment_down");
	}	
	
	function update($input, $seqno) {
		$this->db->update("assessment", $input, array('no' => $seqno));
	}	

	function asses_down_update($input, $seqno) {
		$this->db->update("assessment_down", $input, array('no' => $seqno));
	}	

	function asses_result_update($input, $seqno) {
		$this->db->update("assessment_result", $input, array('no' => $seqno));
	}	

	public function insert2($input) {
		$this->db->insert( "assessment", $input );
	}

	function asses_result_delete($where) {
		if (is_array($where)) {
			$where_option = $this->db->set_where_option($where);
			$this->db->where($where_option, null, false);
		}
		$this->db->delete('assessment_result');
	}	
	
	function asses_down_delete($where) {
		if (is_array($where)) {
			$where_option = $this->db->set_where_option($where);
			$this->db->where($where_option, null, false);
		}
		$this->db->delete('assessment_down');
	}	

	//평가설정
	function select_inout_paging($where, $offset=0, $limit=15) {
		$count_total = $this->db->count_all_results('assessment_settion');
		
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}		

		$this->db->order_by("no", "desc");
		$this->db->select('SQL_CALC_FOUND_ROWS *,', FALSE);
		$query = $this->db->get("assessment_settion");		

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');		
		$count_search = $rs_count->row()->Count;

		$return = array(
				'count'=> array(
						'total'=>$count_total,
						'search'=>$count_search
				),
				'list'=>$query->result_array()
		);
		return $return;
	}	

	function inout_settion_view( $seqno ) {
		$this->db->select('*');
		$this->db->where('no', $seqno );
		$query = $this->db->get( "assessment_settion" );
		$row = $query->row_array();
		return $row;
	}

	function inout_assessment_view( $seqno ) {
		$this->db->select('*');
		$this->db->where('no', $seqno );
		$query = $this->db->get( "assessment" );
		$row = $query->row_array();
		return $row;
	}

	function inout_assessment_view_re( $userid , $re_no ) {
		$this->db->select('*');
		$this->db->where('userid', $userid );
		$this->db->where('re_no', $re_no );
		$query = $this->db->get( "assessment" );
		$row = $query->row_array();
		return $row;
	}

	function get_user_info_list( $where ){

		$position_list = $this->config->item( 'position_code' );
		$duty_list = $this->config->item( 'duty_code' );
	
		if (is_array($where)) {
			$where_option = $this->db->set_where_option($where);
			$this->db->where($where_option, null, false);
		}

		$this->db->order_by("assessment.no", "desc");
		$this->db->select('assessment.no ,assessment.re_no ,user_info.name,user_info.hst_code,user_info.dept_code,user_info.duty_code,user_info.team_code,user_info.position_code ');
		$this->db->from('assessment');
		$this->db->join('user_info', 'assessment.userid=user_info.user_id' , 'left');

		$query = $this->db->get();
		$result = $query->result_array();		
		
		foreach ( $result as $i => $row ) {
			if( $what == "team_s"){
				$list[$row['no']] = $row['name'];
			}
			else{
				$list[$row['no']] = $row['name']."(".trim($position_list[$row['position_code']])."/".trim($duty_list[$row['duty_code']]).")";
			}
		}
		return $list;
	}	
	
	// 평가 리스트
	function select_assessindex_list($where, $offset=0, $limit=15) {
		$count_total = $this->db->count_all_results('assessment');
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by("assessment.no", "desc");		
		$this->db->select('SQL_CALC_FOUND_ROWS *, assessment_settion.is_exposure , assessment.no ,assessment.a_result_percent ,assessment_settion.biz_info as biz_infos,assessment.a_competency_percent,assessment.a_various_percent, assessment_settion.syear ,assessment_settion.firsthalf', FALSE);
		$this->db->from('assessment');
		$this->db->join('user_info', 'assessment.userid=user_info.user_id' , 'left');
		$this->db->join('assessment_settion', 'assessment_settion.no=assessment.re_no' , 'left');
		$rs = $this->db->get();

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		
		$return = array(
				'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
				),
				'list'=>$rs->result_array('no')
		);		
		
		return $return;
	}

	function select_inout_avg($seq_no) {
	
		$this->db->where('a_reidx', $seq_no );
	
		$this->db->select(' ROUND ( SUM( ( pweight *  progress / 100 ) ) )  AS progress,  ROUND ( SUM( ( pweight *  assessment / 100 ) ) )  AS assessment , ROUND ( SUM( ( pweight *  a_greetings_princ / 100 ) ) )  AS a_greetings_princ ');
		$this->db->from('assessment_result');
		$rs = $this->db->get();
		$row = $rs->row_array();
		return $row;	
	}

	function select_inout_avgs( $seq_no ,  $code  , $a_seq_no ) {
	
		if ( $code == 'E'  ) {
			$this->db->where('b_reidx', $seq_no );
			$this->db->where('a_reidx', $a_seq_no );
			$this->db->where('code', $code );			
		} else {
			$this->db->where('a_reidx', $seq_no );
			$this->db->where('code', $code );			
		}

		$this->db->select(' ROUND ( ( SUM( b_user_princ )  /  COUNT(`no`) ))  AS `b_user_princ` , ROUND ( ( SUM( b_team_princ )  /  COUNT(`no`) ))  AS `b_team_princ` , ROUND ( ( SUM( b_greetings_princ )  /  COUNT(`no`) ) ) AS `b_greetings_princ` , ROUND ( ( SUM( c_greetings_princ )  /  COUNT(`no`) ) ) AS `c_greetings_princ`  ');
		$this->db->from('assessment_down');
		$rs = $this->db->get();
		$row = $rs->row_array();
		return $row;	
	}

	function select_inout_view($seq_no) {
	
		$this->db->where('ons.no', $seq_no );
	
		$this->db->select('SQL_CALC_FOUND_ROWS *, ons.no , assessment_settion.syear , assessment_settion.firsthalf ', FALSE);
		$this->db->from('assessment AS ons');
		$this->db->join('user_info', 'ons.userid=user_info.user_id' , 'left');
		$this->db->join('assessment_settion', 'assessment_settion.no=ons.re_no' , 'left');

		$rs = $this->db->get();

		$row = $rs->row_array();
		return $row;
	
	}
	
	function select_inout_details( $where ) {
		
		$count_total = $this->db->count_all_results('assessment_details');

		if (is_array( $where )) {		
			$this->common_lib->set_where($where);
		}			
				
		$this->db->order_by("no", "desc");
		$query = $this->db->get("assessment_details");
		
		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		
		$return = array(
				'count'=> array(
						'total'=>$count_total,
						'search'=>$count_search
				),
				'list'=>$query->result_array()
		);
		return $return;		
	}
	
	function select_assess_list($where ) {
	
		$count_total = $this->db->count_all_results('assessment_down');	
		$this->common_lib->set_where($where);
	
	/*
		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}
	*/
		$this->db->order_by("assessment_down.no", "desc");
	
		$this->db->select('SQL_CALC_FOUND_ROWS * , assessment_down.no '  , FALSE);
		$this->db->from('assessment_down');
		$this->db->join('assessment', 'assessment_down.a_reidx = assessment.no' , 'left');
		$this->db->join('assessment_details', 'assessment_down.adetails_reidx = assessment_details.no' , 'left');
		/*
	SELECT *, assessment.userid FROM `assessment_down`  
	LEFT JOIN assessment ON assessment_down.a_reidx = assessment.no
	LEFT JOIN assessment_details ON assessment_down.adetails_reidx = assessment_details.no
		 */
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
	
	function select_assess_reslt_list($where ) {
	
		$count_total = $this->db->count_all_results('assessment_result');	
		$this->common_lib->set_where($where);
	
		$this->db->order_by("assessment_result.no", "desc");
	
		$this->db->select('SQL_CALC_FOUND_ROWS * , assessment_result.no '  , FALSE);
		$this->db->from('assessment_result');
		$this->db->join('assessment', 'assessment_result.a_reidx = assessment.no' , 'left');

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


	function select_inout_paging3($where) {
	
		if (is_array($where)) {
			$where_option = $this->db->set_where_option($where);
			$this->db->where($where_option, null, false);
		}	
	
	//	$this->db->order_by("assessment.no", "desc");
	
		$this->db->select(' *, assessment.no' );
		$this->db->from('assessment ');
		$this->db->join('user_info', 'assessment.userid=user_info.user_id' , 'left');
	
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

	function select_inout_paging4($user_no) {
	/*
		if (is_array($where)) {
			$where_option = $this->db->set_where_option($where);
			$this->db->where($where_option, null, false);
		}	
	*/
	//	$this->db->order_by("assessment.no", "desc");
	    $this->db->where( " CONCAT(',',competency_val,',') LIKE '%,".$user_no.",%' " , null , false  );
	
		$this->db->select(' *, assessment.no' );
		$this->db->from('assessment ');
		$this->db->join('user_info', 'assessment.userid=user_info.user_id' , 'left');
	
		$rs = $this->db->get();				
		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			'listuser'=>$rs->result_array()
		);
		return $return;
	}	

	function select_inout_paging3_view($where) {
	
		$this->common_lib->set_where($where);	
	
		$this->db->select('*, user_info.name');
		$this->db->from('assessment ');
		$this->db->join('user_info', 'assessment.userid=user_info.user_id' , 'left');

		$rs = $this->db->get();

		$row = $rs->row_array();
		return $row;
	
	}

}
