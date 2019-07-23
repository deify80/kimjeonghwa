<?php
/**
 * 재고관리 Model
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Stock_model extends CI_Model {

	function __construct() {
		parent::__construct();

	}

	/**
	 * 분류데이터 조건 카운팅
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	function count_category($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('stock_category');
		// echo $this->db->last_query();
		return $count;
	}

	/**
	 * 분류 추가
	 * @param  array $record 입력데이터
	 * @return boolean         [description]
	 */
	function insert_category($record) {
		$rs = $this->db->insert('stock_category',$record);
		return $rs;
	}

	/**
	 * 분류 변경
	 * @param  array $record 수정데이터
	 * @param  array $where  조건
	 * @return boolean 결과
	 */
	function update_category($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('stock_category',$record);
		return $rs;
	}

	function select_category($where, $field='*', $key='no') {
		$this->db->select($field, FALSE);
		$this->common_lib->set_where($where);
		$query = $this->db->get('stock_category');
		return $query->result_array($key);
	}

	function select_category_row($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('stock_category');
		return $query->row_array();
	}

	/**
	 * 물품목록
	 * @param  [type]  $where  [description]
	 * @param  integer $offset [description]
	 * @param  integer $limit  [description]
	 * @return [type]          [description]
	 */
	function select_goods_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('stock_goods as g');

		$where = array_merge($where, $where_offset);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);

		$rs = $this->db->get('stock_goods as g');
		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;



		///////// 입고액, 사용액, 재고액
		$this->db->select('i.kind, i.category_no, sum(i.qty) qty, sum(g.unit_price) unit_price, sum(i.qty*g.unit_price) as price', FALSE);

		$this->common_lib->set_where($where);
		$this->db->from('stock_inout i');
		$this->db->join('stock_goods g', 'i.goods_no = g.no');
		$this->db->group_by('i.kind, i.category_no');
		$price_query = $this->db->get();
		$price_list = $price_query->result_array();


		///////// 입고액, 사용액, 재고액




		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			'list'=>$rs->result_array(),
			'price_list'=>$price_list
		);
		return $return;
	}

	function select_goods_row($where) {
		$this->db->where($where);
		$query = $this->db->get('stock_goods');
		return $query->row_array();
	}

	function select_goods($where, $field='*', $key='no') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('stock_goods');
		return $query->result_array($key);
	}


	/**
	 * 물품 조건 카운팅
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	function count_goods($where) {
		$this->common_lib->set_where($where);
		$count = $this->db->count_all_results('stock_goods');
		// echo $this->db->last_query();
		return $count;
	}

	/**
	 * 물품 추가
	 * @param  array $record 입력데이터
	 * @return boolean         [description]
	 */
	function insert_goods($record) {
		$rs = $this->db->insert('stock_goods',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else return false;
	}

	/**
	 * 물품 변경
	 * @param  array $record 수정데이터
	 * @param  array $where  조건
	 * @return boolean 결과
	 */
	function update_goods($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('stock_goods',$record);
		return $rs;
	}

	function insert_qty($record) {

		$sql = $this->db->insert_string('stock_goods_qty', $record);

		foreach($record as $field=>$value) {
			if(in_array($field, array('goods_no','biz_id', 'date_insert'))) continue;
			$update_record[] = "{$field}='{$value}'";
		}
		$sql .=" ON DUPLICATE KEY UPDATE ".implode(', ',$update_record);

		$rs = $this->db->query($sql);
		return $rs;
	}

	function update_qty($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('stock_goods_qty', $record);
		return $rs;
	}

	function select_qty_row($where, $field='*') {
		$this->db->select($field);
		$this->db->where($where);
		$query = $this->db->get('stock_goods_qty');
		// echo $this->db->last_query();
		return $query->row_array();
	}

	/**
	 * 재고 수량
	 * @param  [type] $where [description]
	 * @param  string $field [description]
	 * @param  string $key   [description]
	 * @return [type]        [description]
	 */
	function select_qty($where, $field='*', $key='') {
		$this->db->select($field);
		$this->db->where($where);
		$query = $this->db->get('stock_goods_qty');
		return $query->result_array($key);
	}


	/**
	 * 입출고 내역 등록
	 * @return [type] [description]
	 */
	function insert_inout($record) {
		$rs = $this->db->insert('stock_inout',$record);
		// echo $this->db->last_query();
		if($rs) {
			return $this->db->insert_id();
		}
		else return false;
	}

	/**
	 * 입출고 내역 수정
	 * @return [type] [description]
	 */
	function update_inout($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('stock_inout', $record);
		return $rs;
	}

	/**
	 * 입출고내역 삭제
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	function delete_inout($where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->delete('stock_inout');
		return $rs;
	}

	/**
	 * 물품목록
	 * @param  [type]  $where  [description]
	 * @param  integer $offset [description]
	 * @param  integer $limit  [description]
	 * @return [type]          [description]
	 */
	function select_inout_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->db->where($where_offset);
		$this->db->join('stock_goods AS goods', 'io.goods_no = goods.no');
		$count_total = $this->db->count_all_results('stock_inout as io');

		$where = array_merge($where, $where_offset);

		//입/출고액

		$this->db->select('goods.classify_code AS classify_code, io.kind AS kind, SUM(goods.unit_price* io.qty) AS price', FALSE);

		$this->db->start_cache();
		$this->common_lib->set_where($where);
		$this->db->from('stock_inout AS io');
		$this->db->join('stock_goods AS goods', 'io.goods_no = goods.no');
		$this->db->join('user_info AS user', 'io.writer_id = user.user_id');
		$this->db->stop_cache();
		$this->db->group_by('io.kind, goods.classify_code');
		$sum_query = $this->db->get();
		$sum = $sum_query->result_array();

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}
		$this->db->order_by('io.date_inout DESC, no DESC');
		$this->db->select("SQL_CALC_FOUND_ROWS io.*, goods.goods_name, goods.classify_code, goods.item_code, goods.unit, goods.unit_price, user.name AS writer_name", FALSE);
		$rs = $this->db->get();
		$this->db->flush_cache();



		$rs_count = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
		$count_search = $rs_count->row()->Count;
		$return = array(
			'count'=> array(
				'total'=>$count_total,
				'search'=>$count_search
			),
			'sum'=>$sum,
			'list'=>$rs->result_array()
		);
		return $return;
	}

	function select_inout_row($where, $field='io.*', $orderby='io.date_inout DESC') {
		$this->common_lib->set_where($where);
		$this->db->select($field);
		$this->db->order_by($orderby);
		$this->db->from('stock_inout AS io');
		$this->db->join('stock_goods AS g', 'io.goods_no = g.no');
		$query = $this->db->get();
		return $query->row_array();
	}


	function select_inout_groupby($where, $field, $group_by) {
		$this->common_lib->set_where($where);
		$this->db->select($field, false);
		$this->db->group_by($group_by);
		$query = $this->db->get('stock_inout');
		// echo $this->db->last_query();
		return $query->result_array($group_by);
	}



	// 20170406 kruddo - 재료비분류설정
	function insert_category_product($record) {
		$rs = $this->db->insert('stock_category_product',$record);
		return $rs;
	}


	function select_category_sub($where, $field='*', $key='no') {
		$this->db->select('@RNUM := 0');
		$query = $this->db->get();
		$query->result_array();

		$this->common_lib->set_where($where);
		$this->db->select($field);
		$this->db->from('stock_category_product AS p');
		$this->db->join('stock_goods AS g', 'p.goods_no = g.no');
		$this->db->order_by('p.no desc');
		$query = $this->db->get();
		return $query->result_array();

	}

	public function delete($no) {
		$this->db->where( 'no', $no );
		$this->db->delete( "stock_category_product" );
	}
	// 20170406 kruddo - 재료비분류설정

	/**
	 * 거래처 추가
	 */
	public function insert_client($record) {
		$rs = $this->db->insert('client',$record);
		return $rs;
	}

	/**
	 * 거래처업데이트
	 */
	public function update_client($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('client',$record);
		return $rs;
	}

	public function select_client_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->db->where($where_offset);
		$count_total = $this->db->count_all_results('client');


		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$rs = $this->db->get('client');

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

	public function select_client_row($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('client');
		return $query->row_array();
	}

	public function select_client_all($where=array(), $field='*', $idx='') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('client');
		return $query->result_array($idx);
	}


	public function delete_client($where) {
		$this->common_lib->set_where($where);
		$rs = $this->db->delete('client');
		return $rs;
	}

	/**
	 * 발주서 등록
	 * @param  [type] $record [description]
	 * @return [type]         [description]
	 */
	public function insert_order($record) {
		$rs = $this->db->insert('stock_order',$record);
		if($rs) {
			return $this->db->insert_id();
		}
		else return false;
	}

	public function insert_order_goods($record) {
		$rs = $this->db->insert('stock_order_goods',$record);
		return $rs;
	}

	public function update_order_goods($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('stock_order_goods',$record);
		return $rs;
	}

	public function select_order_row($where, $field='*') {
		$this->db->select($field);
		$this->common_lib->set_where($where);
		$query = $this->db->get('stock_order');
		return $query->row_array();
	}

	public function select_order_paging($where, $offset=0, $limit=15, $where_offset=array()) {
		$this->common_lib->set_where($where_offset);
		$count_total = $this->db->count_all_results('stock_order');

		$where = array_merge($where_offset, $where);
		$this->common_lib->set_where($where);

		if(!is_null($offset)) {
			if($limit) $this->db->limit($limit, $offset);
		}

		$this->db->order_by('no','DESC');
		$this->db->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$rs = $this->db->get('stock_order');
		//echo $this->db->last_query();

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

	public function select_order_goods($where, $field='*') {
		//$this->db->select($field);
		$this->db->select('g.*, og.cnt, og.cnt_order, og.client_no as client_order');
		$this->common_lib->set_where($where);
		$this->db->from('stock_order_goods og');
		$this->db->join('stock_goods g', 'og.goods_no = g.no');


		$query = $this->db->get();
		return $query->result_array('goods_no');
	}

	public function update_order($record, $where) {
		$this->db->where($where);
		$rs = $this->db->update('stock_order',$record);
		//echo $this->db->last_query();
		return $rs;
	}
}
