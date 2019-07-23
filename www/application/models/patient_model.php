<?php
/**
 * 환자관리 Model
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Patient_model extends CI_Model {

	function __construct() {
		parent::__construct();

	}

	function select_patient_paging($where, $offset=0, $limit=15, $where_offset = array()) {
		$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('patient');


		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$rs = $this->db->get('patient');

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

	public function select_patient_project_paging($where, $offset=0, $limit=15, $where_offset = array()) {
		$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('patient');


		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$rs = $this->db->get('patient');

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

	function count_patient($where, $tbl='patient') {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results($tbl);
		return $count;
	}

	function insert_patient($record, $tbl='patient') {
		$rs = $this->db->insert($tbl,$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else  return false;
	}

	function update_patient($record, $where, $tbl='patient') {
		$this->common_lib->set_where($where);
		$rs = $this->db->update($tbl,$record);
		return $rs;
	}

	function remove_patient($where, $tbl='patient'){
		$this->common_lib->set_where($where);
		$rs = $this->db->delete($tbl);
		return $rs;
	}

	function select_patient_row($where, $tbl='patient', $field='*') {
		$this->db->select($field);
		$this->db->where($where);
		$query = $this->db->get($tbl);
		return $query->row_array();
	}

	function select_patient_all($where, $tbl='patient', $field='*', $order_by='', $key='', $group_by='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		if($order_by) $this->db->order_by($order_by);
		if($group_by) $this->db->group_by($group_by);

		$query = $this->db->get($tbl);
		return $query->result_array($key);
	}

	function select_patient_join($where, $tbl='patient', $field='*', $order_by='', $key='', $group_by='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		if($order_by) $this->db->order_by($order_by);
		if($group_by) $this->db->group_by($group_by);
		$this->db->from($tbl.' AS sub');
		$this->db->join('patient AS p', 'p.no=sub.patient_no');

		$query = $this->db->get();
		return $query->result_array($key);
	}



	function count_patient_grade($where) {
		$this->common_lib->set_where($where);
		$this->db->group_by('grade_no');
		$this->db->select("grade_no, SUM(1) AS cnt", FALSE);
		$query = $this->db->get('patient');
		return $query->result_array('grade_no');
	}

	function select_appointment($where, $field="p.name, p.chart_no, p.stay_status, p.mobile, p.messenger, p.manager_team_code, p.sex, p.birth, p.path_code, p.grade_no, p.grade_type, p.is_o, p.comment as patient_comment, pa.*, (pa.visit+0) as visit_code") {
		$this->db->select($field, FALSE);
		$this->db->from('patient_appointment AS pa');
		$this->db->join('patient AS p', 'p.no = pa.patient_no');
		$this->common_lib->set_where($where);
		$query = $this->db->get();
		if($return == 'row') {
			return $query->row_array();
		}
		else {
			return $query->result_array();
		}
	}

	function select_appointment_row($where, $field="p.name, p.mobile, p.messenger, pa.*, (pa.visit+0) as visit_code") {
		$rs = $this->select_appointment($where, $field);
		return array_shift($rs);
	}

	function select_appointment_count($where) {
		$this->db->where($where);
		$this->db->from('patient_appointment');
		return $this->db->count_all_results();
	}

	/**
	 * 마지막 예약일자정보
	 * @return [type] [description]
	 */
	function select_appointment_last($where) {
		$this->common_lib->set_where($where);
		$this->db->select('appointment_date');
		$this->db->order_by('appointment_date','DESC');
		$this->db->limit(1);
		$query = $this->db->get('patient_appointment');
		$rs = $query->row_array();
		return $rs['appointment_date'];
	}

	function select_widget_row($tbl, $where=array(), $field="*") {
		$this->db->select($field, FALSE);
		if(!empty($where)) $this->common_lib->set_where($where);
		$query = $this->db->get($tbl);
		// echo $this->db->last_query();
		return $query->row_array();
	}

	function select_widget_paging($tbl='patient_appointment', $where, $offset=0, $limit=15, $order_by='') {
		$count_total = $this->db->count_all_results($tbl);
		// $where['is_delete']='N';
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}


		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		if($order_by) $this->db->order_by($order_by);
		else $this->db->order_by('no','DESC');
		$rs = $this->db->get($tbl);

		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			// 'result'=>array('no'=>$search_no['no']),
			'list'=>$rs->result_array()
		);
		return $return;
	}

	/**
	 * 예약
	 */
	function select_appointment_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		if($where_offset) $this->db->where($where_offset);
		$this->db->from('patient_appointment AS pa');
		$this->db->join('patient AS p', 'p.no = pa.patient_no');
		$count_total = $this->db->count_all_results();


		$where = array_merge($where_offset, $where);

		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		//$this->db->order_by('pa.no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS pa.*, p.name, p.chart_no, p.sex, p.mobile', FALSE);
		$this->db->from('patient_appointment AS pa');
		$this->db->order_by('pa.appointment_date ASC');
		$this->db->order_by('pa.appointment_time_start ASC');

		$this->db->join('patient AS p', 'p.no = pa.patient_no');
		$rs = $this->db->get();

		//echo $this->db->last_query();


		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			// 'result'=>array('no'=>$search_no['no']),
			'list'=>$rs->result_array()
		);
		return $return;
	}

	/**
	 * 수납
	 * @param  [type]  $where  [description]
	 * @param  integer $offset [description]
	 * @param  integer $limit  [description]
	 * @return [type]          [description]
	 */
	function select_pay_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->db->where($where_offset);
		$this->db->from('patient_pay AS pp');
		$this->db->join('patient AS p', 'p.no = pp.patient_no');
		$count_total = $this->db->count_all_results();


		$where = array_merge($where_offset, $where);

		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('pp.no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS pp.*, p.name, p.chart_no, p.sex, p.mobile', FALSE);
		$this->db->from('patient_pay AS pp');
		$this->db->join('patient AS p', 'p.no = pp.patient_no');
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
			'list'=>$rs->result_array()
		);
		return $return;
	}

	function select_pay_row($where=array(), $field="*") {
		$this->db->select($field, FALSE);
		if(!empty($where)) $this->common_lib->set_where($where);
		$this->db->from('patient_pay AS pp');
		$this->db->join('patient AS p', 'p.no = pp.patient_no');
		$query = $this->db->get();
		return $query->row_array();
	}

	function select_project_row($where=array(), $field="*") {
		$rs = $this->select_project_all($where, $field);
		return array_shift($rs);
		// $this->db->select($field, FALSE);
		// if(!empty($where)) $this->common_lib->set_where($where);
		// $this->db->from('patient_project AS pp');
		// $this->db->join('patient AS p', 'p.no = pp.patient_no');
		// $query = $this->db->get();
		// return $query->row_array();
	}

	function select_project_all($where=array(), $field='*', $orderby='pp.no DESC') {
		$this->db->select($field, FALSE);
		if(!empty($where)) $this->common_lib->set_where($where);
		$this->db->order_by($orderby);
		$this->db->from('patient_project AS pp');
		$this->db->join('patient AS p', 'p.no = pp.patient_no');
		$query = $this->db->get();
		// echo $this->db->last_query();
		return $query->result_array();
	}



	function  select_project_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->db->where($where_offset);
		$this->db->from('patient_project AS pp');
		$this->db->join('patient AS p', 'p.no = pp.patient_no', 'LEFT');
		$count_total = $this->db->count_all_results();

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('pp.date_project','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS pp.*, p.name, p.chart_no, p.sex, p.mobile, p.birth', FALSE);
		$this->db->from('patient_project AS pp');
		$this->db->join('patient AS p', 'p.no = pp.patient_no', 'LEFT');
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
			'list'=>$rs->result_array()
		);
		return $return;
	}


	// 20170208 kruddo : consulting_info, patient_pay - join
	function select_consulting_info_join($where, $field='*', $orderby=null, $groupby=null) {
		$this->db->select($field);
		$this->common_lib->set_where($where);

		$this->db->where( 'c.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'c.use_flag', 'Y' );

		if(!$orderby) $this->db->order_by('c.team_code');
		else			$this->db->order_by($orderby);
		if(!$groupby)	$this->db->group_by('c.team_code');
		else			$this->db->group_by($groupby);
		//$this->db->group_by('c.cst_status');

		$this->db->from('db_info AS db');
		$this->db->join('consulting_info AS c', 'db.db_seqno=c.db_seqno', 'left');

		$query = $this->db->get();
		return $query->result_array();
	}

	function select_consulting_info_doctor_join($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);

		$this->db->where( 'c.hst_code', $this->session->userdata( 'ss_hst_code' ) );
		$this->db->where( 'c.use_flag', 'Y' );

		$this->db->order_by('pa.doctor_id');
		$this->db->group_by('pa.doctor_id');
		//$this->db->group_by('c.cst_status');

		$this->db->from('db_info AS db');
		$this->db->join('consulting_info AS c', 'db.db_seqno=c.db_seqno', 'left');
		$this->db->join('patient_appointment AS pa', 'c.patient_no=pa.patient_no', 'left');

		$query = $this->db->get();
		return $query->result_array();
	}

	function select_consulting_info_patient_join($where, $field='*', $group_by='', $order_by='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->where( 'ci.use_flag', 'Y' );
		$this->db->where( 'p.is_delete', 'N' );

		if($order_by) $this->db->order_by($order_by);
		if($group_by) $this->db->group_by($group_by);

		$this->db->from('patient AS p');
		$this->db->join('consulting_info AS ci', 'p.no=ci.patient_no');
		$this->db->join('patient_appointment AS pa', 'p.no=pa.patient_no');

		$query = $this->db->get();
		return $query->result_array();
	}


	function select_consulting_info_project_join($where, $field='*', $group_by='', $order_by='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->where( 'db.use_flag', 'Y' );
		$this->db->where( 'pp.is_delete', 'N' );

		if($order_by) $this->db->order_by($order_by);
		if($group_by) $this->db->group_by($group_by);

		$this->db->from('consulting_info AS db');
		$this->db->join('patient_project AS pp', 'db.patient_no=pp.patient_no', 'left');

		$query = $this->db->get();
		return $query->result_array();
	}

	function select_consulting_info_pay_join($where, $field='*', $group_by='', $order_by='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->where( 'db.use_flag', 'Y' );
		$this->db->where( 'pp.is_delete', 'N' );
		$this->db->where( '(ppp.is_delete="N" or ppp.is_delete is null)' );

		//$this->db->order_by('db.team_code');
		//$this->db->group_by('db.team_code');
		if($order_by) $this->db->order_by($order_by);
		if($group_by) $this->db->group_by($group_by);

		$this->db->from('consulting_info AS db');
		$this->db->join('patient_project AS pp', 'db.patient_no=pp.patient_no', 'left');
		$this->db->join('patient_pay AS ppp', 'pp.no=ppp.project_no', 'left');

		$query = $this->db->get();
		return $query->result_array();
	}


	function select_consulting_info_join_project($where, $field='*', $group_by='', $order_by='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->where( 'ci.use_flag', 'Y' );
		$this->db->where( 'pp.is_delete', 'N' );
		$this->db->where( '(ppp.is_delete="N" or ppp.is_delete is null)' );

		$this->db->from('consulting_info AS ci');
		$this->db->join('patient_project AS pp', 'ci.patient_no=pp.patient_no', 'left');
		$this->db->join('patient_pay AS ppp', 'pp.no=ppp.project_no', 'left');

		if($order_by) $this->db->order_by($order_by);
		if($group_by) $this->db->group_by($group_by);

		$query = $this->db->get();

		$return = array(
			'list'=>$query->result_array()
		);

		return $return;
	}

	// 미수금
	function select_consulting_info_join_unpaid_project($where, $field='*', $group_by='', $order_by='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$this->db->where( 'ci.use_flag', 'Y' );
		$this->db->where( 'pp.is_delete', 'N' );

		$this->db->from('consulting_info AS ci');
		$this->db->join('patient_project AS pp', 'ci.patient_no=pp.patient_no', 'left');

		if($order_by) $this->db->order_by($order_by);
		if($group_by) $this->db->group_by($group_by);

		$query = $this->db->get();

		$return = array(
			'list'=>$query->result_array()
		);

		return $return;
	}

	function  select_consulting_team_detail_paging($where, $offset=0, $limit=15, $where_offset=array(), $field='*') {

		$this->db->where($where_offset);
		$this->db->where( 'ci.use_flag', 'Y' );
		$this->db->where( 'pp.is_delete', 'N' );
		//$this->db->where( '(ppp.is_delete="N" or ppp.is_delete is null)' );


		$this->db->from('consulting_info AS ci');
		$this->db->join('patient_project AS pp', 'ci.patient_no=pp.patient_no', 'left');
		//$this->db->join('patient_pay AS ppp', 'pp.no=ppp.project_no', 'left');
		$count_total = $this->db->count_all_results();

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}


		$this->db->select('SQL_CALC_FOUND_ROWS '.$field, FALSE);
		$this->db->from('consulting_info AS ci');
		$this->db->join('patient_project AS pp', 'ci.patient_no=pp.patient_no', 'left');
		//$this->db->join('patient_pay AS ppp', 'pp.no=ppp.project_no', 'left');

		$this->db->order_by('pp.date_project','ASC');
		$this->db->order_by('pp.no','DESC');
		$this->db->group_by('pp.patient_no, pp.date_project');
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
	// 20170208 kruddo : consulting_info, patient_pay - join


}
