<?php
/**
 * 재고관리
 * 작성 : 2015.05.20
 * @author 이혜진
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Stock extends CI_Controller {

	var $dataset;
	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;

		$this->load->model( array (
			'Stock_Model'
			// 'Attendance_model'
		) );
	}

	/**
	 * 재고분류 설정 레이아웃
	 * @return [type] [description]
	 */
	public function settings() {
		$group = $this->uri->segment(3);
		$datum = array(
			'cfg'=>array(
				'group'=>$group
			)
		);
		$this->_render('settings', $datum );
	}

	/**
	 * 분류 목록
	 * @return json
	 */
	public function category_list() {
		$where = $this->param;
		$rs = $this->Stock_Model->select_category($where);
		if($rs) {
			return_json(true, '', $rs);
		}
		else {
			return_json(false,'');
		}
	}

	/**
	 * 분류명 추가/수정
	 * @return void
	 */
	public function classify_input() {
		$no = $this->input->post('no');
		$code_parent = $this->input->post('code_parent');
		$kind = 'classify';
		if($code_parent == '000S')		$kind = 'subject';

		if($no) {
			$mode  = 'update';
			$rs = $this->Stock_Model->select_category_row(array('no'=>$no));
		}
		else {
			$mode = 'insert';
			$rs = array(
				'kind'=>$kind,
				'code_parent'=>$code_parent,  //병원코드(000H), 총무팀코드(000C),			 과목(000S) 20170404 kruddo - 추가
				'is_use'=>'Y'
			);
		}

		$datum = array(
			'cfg'=>array(
				'mode'=>$mode
			),
			'rs'=>$rs
		);
		$this->_display('classify_input', $datum );
	}

	/**
	 * 항목 등록, 수정
	 * @return [type] [description]
	 */
	public function item_input() {
		$no = $this->input->post('no');
		if($no) {
			$mode  = 'update';
			$rs = $this->Stock_Model->select_category_row(array('no'=>$no));
		}
		else {
			$mode = 'insert';
			$rs = array(
				'kind'=>'item',
				'code_parent'=>$this->param['code_parent'],
				'is_use'=>'Y'
			);
		}

		//분류리스트
		$categories = $this->Stock_Model->select_category(array('kind'=>'classify'), 'name, code, CONCAT(code_parent,"_",code) AS code_real', 'code_real');
		$datum = array(
			'cfg'=>array(
				'mode'=>$mode,
				'categories'=>$categories
			),
			'rs'=>$rs
		);
		$this->_display('item_input', $datum );
	}

	/**
	 * 물품목록 레이아웃
	 * @return void
	 */
	public function goods() {
		$group = $this->uri->segment(3);
		$srch_date = get_search_type_date(); //검색날짜
		$classify_list = $this->Stock_Model->select_category(array('kind'=>'classify', 'is_use'=>'Y', 'code_parent'=>$group), 'name, code', 'code');
		$datum = array(
			'cfg'=>array(
				'group'=>$group,
				'date'=>$srch_date,
				'classify'=>$classify_list
			),
			'auth'=>array(
				'goods_down'=>$this->common_lib->check_auth_group('goods_down')
			)
		);
		$this->_render('goods', $datum );
	}

	/**
	 * 물품리스트
	 * @return [type] [description]
	 */
	function goods_list_paging($assoc='') {
		$p = $this->param;

		if(empty($assoc)) {
			parse_str($this->input->post('search'), $assoc);
		}


		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		// $page = $this->input->post('page');
		// $limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		// $offset = ($page-1)*$limit;

		//검색조건설정
		//
		$where_offset = array('group_code'=>$assoc['group_code'], 'is_delete'=>'N', 'g.biz_id'=>$this->session->userdata('ss_biz_id'));
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page','limit', 'type'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'goods_name':
					$where['goods_name LIKE']="%{$v}%";
				break;
				case 'date_s':
					$where['g.date_insert >=']="{$v}";
				break;
				case 'date_e':
					$where['g.date_insert <=']="{$v}";
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$rs = $this->Stock_Model->select_goods_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {
			//분류명
			$categories = $this->Stock_Model->select_category(array('kind != '=>'group', 'code_parent LIKE '=>$assoc['group_code']."%"), 'code, name', 'code');

			//현재수량, 최소보유수량
			$biz_id = $this->session->userdata('ss_biz_id');


			//최근입출고내역
			// $inout = $this->Stock_Model->select_inout_last(array('goods_no'=>$no,'biz_id'=>$biz_id));
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$where = array('goods_no'=>$row['no'], 'g.biz_id'=>$biz_id);
				$qty = $this->Stock_Model->select_qty_row(array('goods_no'=>$row['no'], 'biz_id'=>$biz_id));
				$last_inout = $this->Stock_Model->select_inout_row($where, 'kind, qty');
				$row['inout'] = $last_inout;
				$row['classify_name'] = $categories[$row['classify_code']]['name'];
				$row['item_name'] = $categories[$row['item_code']]['name'];
				$row['qty_stock'] = $qty['qty_stock'];
				$row['qty_min'] = $qty['qty_min'];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}

			// pre($rs);

		}

		//$price_list = $rs['price_list'];
		$category_subject = $this->Stock_Model->select_category(array('code_parent'=>'000S', 'is_use'=>'Y'));
		foreach($category_subject as $row){

			foreach($rs['price_list'] as $rows){
				if($row['no'] == $rows['category_no']){

					if($rows['kind'] == 'in')				$row['in'] += $rows['price'];
					else if($rows['kind'] == 'out')			$row['out'] += $rows['price'];
					else if($rows['kind'] == 'sale')		$row['sale'] += $rows['price'];
					else	$row[''] += $rows['price'];

					//$row[$row['no']][$rows['kind']] += $rows['price'];
					//$row[$row['no']][$rows['kind']] = $rows['category_no'];//[$rows['price']];//[$rows['price']];
				}
				else if($row['no'] == ''){
					$row[''][$rows['kind']] += $rows['price'];
				}
			}
			$price_list[] = $row;
		}

		/*
		$aaa = array(
			'rs'=>$rs['price_list'],
			'price'=>$price_list
		);
		return_json(true, '', $aaa);
		*/


		/*
		foreach($rs['price_list'] as $row){


			$price_list[$row['category_no']][$row['kind']]['kind'] = $row['kind'];
			$price_list[$row['category_no']][$row['kind']]['qty'] = $row['qty'];
			$price_list[$row['category_no']][$row['kind']]['unit_price'] = $row['unit_price'];
			$price_list[$row['category_no']][$row['kind']]['price'] = $row['price'];
			$price_list[$row['category_no']]['category_name'] = $category_subject[$row['category_no']]['name'];


			$price_list[$row['kind']][$row['category_no']]['kind'] = $row['kind'];
			$price_list[$row['kind']][$row['category_no']]['qty'] = $row['qty'];
			$price_list[$row['kind']][$row['category_no']]['unit_price'] = $row['unit_price'];
			$price_list[$row['kind']][$row['category_no']]['price'] = $row['price'];
			$price_list[$row['kind']][$row['category_no']]['category_name'] = $category_subject[$row['category_no']]['name'];

			//$price_list[] = $row;
		}
		*/



		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'price_list'=>$price_list

		);

		if($this->output=='excel') {
			return $return;
		}
		else {
			if($rs['count']['search']>0) {
				return_json(true, '', $return);
			}
			else {
				return_json(false, '', $return);
			}
		}

	}

	/**
	 * 물품 목록
	 * @return [type] [description]
	 */
	function goods_list() {
		$goods_name = $this->param['goods_name'];
		$where = array(
			'is_delete'=>'N',
			'biz_id'=>$this->session->userdata('ss_biz_id'),
			'group_code'=>$this->param['group_code']
		);

		if($goods_name) {
			$where["goods_name LIKE '%{$goods_name}%'"]='';
		}
		$rs = $this->Stock_Model->select_goods($where, 'no, goods_name');
		//echo $this->db->last_query();
		if($rs) {
			return_json(true, '', $rs);
		}
		else {
			return_json(false);
		}
	}

	/**
	 * 물품 정보
	 * @return [type] [description]
	 */
	function goods_info() {
		$goods_no = $this->param['no'];
		$group_code = $this->param['group_code'];
		$category_list = $this->Stock_Model->select_category(array('is_use'=>'Y', 'kind != '=>'group', 'code_parent LIKE'=>"{$group_code}%"), 'code, name', 'code');

		$goods_info = $this->Stock_Model->select_goods_row(array('no'=>$goods_no));
		if($goods_info) {
			$goods_info['classify_name'] = $category_list[$goods_info['classify_code']]['name'];
			$goods_info['item_name'] = $category_list[$goods_info['item_code']]['name'];

			$goods_info['gno'] = $goods_info['no'];
			$goods_info['goods_unit_price'] = $goods_info['unit_price'];

			//최초 재고 및 최소보유수량
			$qty = $this->Stock_Model->select_qty(array('goods_no'=>$goods_info['no']),'biz_id, qty_first, qty_min', 'biz_id');
			$goods_info['stock'] = $qty;
			return_json(true, '', $goods_info);
		}
		else {
			return_json(false);
		}
	}

	/**
	 * 물품 등록,수정
	 * @return void
	 */
	public function goods_input() {
		$biz_list = $this->session->userdata('ss_my_biz_list');
		$group = $this->param['group_code'];
		$classify_list = $this->Stock_Model->select_category(array('kind'=>'classify', 'is_use'=>'Y', 'code_parent'=>$group), 'code, name', 'code');

		$no = $this->input->post('goods_no');
		$group_code = $this->input->post('group_code');
		if($no) {
			$mode = 'update';
			$rs = $this->Stock_Model->select_goods_row(array('no'=>$no));
			$qty_rs = $this->Stock_Model->select_qty(array('goods_no'=>$no),'biz_id, qty_first, qty_stock, qty_min', 'biz_id');
			$rs['qty'] = $qty_rs;
			$rs['use_team_arr'] = explode(',',$rs['use_team']);
		}
		else {
			$mode = 'insert';
			$rs = array(
				'biz_id'=>$this->session->userdata('ss_biz_id'),
				'group_code'=>$group_code
			);
		}

		$this->load->model('User_model');
		$team_list = $this->User_model->get_team_list(array('50','60')); //사용팀(의료지원실)
		$client_list = $this->Stock_Model->select_client_all(); //거래처목록

		unset($team_list[53]); //회복팀제외
		unset($team_list[52]); //콜센터제외

		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'biz'=>$biz_list,
				'classify'=>$classify_list,
				'team'=>$team_list,
				'client'=>$client_list
			),
			'auth'=>array(
				'goods_delete'=>$this->common_lib->check_auth_group('goods_delete')
			),
			'rs'=>$rs
		);
		$this->_render('goods_input', $datum, 'inc' );
	}

	/**
	 * 입출고내역
	 * @return void
	 */
	public function inout() {
		$group = $this->uri->segment(3);

		$srch_date = get_search_type_date(); //검색날짜
		$classify_list = $this->Stock_Model->select_category(array('kind'=>'classify', 'code_parent'=>$group), 'name, code', 'code');
		$category_subject = $this->Stock_Model->select_category(array('code_parent'=>'000S', 'is_use'=>'Y'));
		$datum = array(
			'cfg'=>array(
				'group'=>$group,
				'date'=>$srch_date,
				'classify'=>$classify_list,
				'category_subject'=>$category_subject

			),
			'auth'=>array(
				'goods_down'=>$this->common_lib->check_auth_group('goods_down')
			)
		);
		$this->_render('inout', $datum);
	}

	/**
	 * 재고 수량
	 * @return [type] [description]
	 */
	public function get_qty() {
		$goods_no = $this->input->post('goods_no');
		$biz_id = $this->input->post('biz_id');
		$rs = $this->Stock_Model->select_qty_row(array('goods_no'=>$goods_no, 'biz_id'=>$biz_id), 'qty_first, qty_stock, qty_min');
		if($rs) {
			return_json(true, '', $rs);
		}
		else {
			return_json(false);
		}
	}

	/**
	 * 입출고등록 / 수정
	 * @return void
	 */
	public function inout_input() {
		$no = $this->input->post('inout_no');

		$biz_list = $this->session->userdata('ss_my_biz_list');
		$category_subject = $this->Stock_Model->select_category(array('code_parent'=>'000S', 'is_use'=>'Y'));

		if($no) {
			$mode = 'update';
			$rs = $this->Stock_Model->select_inout_row(array('io.no'=>$no), 'io.*, g.goods_name, g.classify_code, g.item_code, g.unit_price');

			$category_list = $this->Stock_Model->select_category(array('is_use'=>'Y', 'kind != '=>'group'), 'code, name', 'code');
			$rs['classify_name'] = $category_list[$rs['classify_code']]['name'];
			$rs['item_name'] = $category_list[$rs['item_code']]['name'];

			$qty = $this->Stock_Model->select_qty_row(array('goods_no'=>$rs['goods_no'], 'biz_id'=>$rs['biz_id']), 'qty_stock, qty_min');
			$rs['stock'] = $qty;


			//첨부파일(증빙서류)
			if($rs['attach_count'] > 0) {

				$this->load->model('Common_Model');
				$rs['attach_list'] = $this->Common_Model->select_file(array('refer_table'=>'stock_inout', 'refer_seqno'=>$no), 'seqno, file_path, file_name');
			}
			// pre($rs);
		}
		else {
			$mode = 'insert';

			$rs = array(
				'attach_count'=>0,
				'group_code'=>$this->input->post('group_code'),
				'biz_id'=>$this->session->userdata('ss_biz_id'),
				'date_inout'=>date('Y-m-d'),
				'kind'=>$this->input->post('kind')
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'biz'=>$biz_list,
				'biz_id'=>$this->session->userdata('ss_biz_id'),
				'category_subject'=>$category_subject
			),
			'auth'=>array(
				'invoice_delete'=>$this->common_lib->check_auth_group('invoice_delete')
			),
			'rs'=>$rs
		);

		// pre($datum);

		$this->_render('inout_input', $datum, 'inc' );
	}

	/**
	 * 입출고
	 * @return [type] [description]
	 */
	function inout_list_paging($assoc='') {
		$p = $this->param;

		if(empty($assoc)) {
			parse_str($this->input->post('search'), $assoc);
		}

		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		// $page = $this->input->post('page');
		// $limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		// $offset = ($page-1)*$limit;

		//검색조건설정
		$where_offset = array('group_code'=>$assoc['group_code'], 'goods.is_delete'=>'N', 'io.biz_id'=>$this->session->userdata('ss_biz_id'));
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','limit', 'type'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["(goods_name LIKE '%{$v}%' OR requester_id LIKE '%{$v}%' OR user.name LIKE '%{$v}%')"]=NULL;
				break;
				case 'date_s':
					$where['date_inout >=']="{$v}";
				break;
				case 'date_e':
					$where['date_inout <=']="{$v}";
				break;
				case 'attach':
					switch($v){
						case 'Y':
							$where['attach_count >']="0";
						break;
						case 'N':
							$where['attach_count <=']="0";
						break;
					}
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$category_subject = $this->Stock_Model->select_category(array('code_parent'=>'000S', 'is_use'=>'Y'));

		$rs = $this->Stock_Model->select_inout_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {
			//분류명
			$categories = $this->Stock_Model->select_category(array('kind != '=>'group', 'code_parent LIKE '=>$assoc['group_code']."%"), 'code, name', 'code');

			$biz_id = $this->session->userdata('ss_biz_id');

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				$row['classify_name'] = $categories[$row['classify_code']]['name'];
				$row['item_name'] = $categories[$row['item_code']]['name'];
				//$row['kind_txt'] = ($row['kind']=='in')?'입고':'사용';
				if($row['kind']=='in')		$kind_txt = '입고';
				else if($row['kind']=='out')		$kind_txt = '사용';
				else $kind_txt = '판매';
				$row['kind_txt'] = $kind_txt;

				$row['category_name'] = $category_subject[$row['category_no']]['name'];

				$row['idx'] = $idx;

				/*
				//첨부파일
				if($row['attach_count'] > 0) {
					$this->load->model('Common_Model');
					$attach = $this->Common_Model->select_file(array('refer_table'=>'stock_inout', 'refer_seqno'=>$row['no']), 'file_path');
				}

				$row['attach_txt'] = ($row['kind']=='in')?'거래명세서':'의약품폐기';
				$row['attach'] = $attach;
				*/
				$list[] = $row;
				$idx--;
			}
		}

		//입/출고액
		$sum = array();
		if(is_array($rs['sum'])) {
			foreach($rs['sum'] as $row) {
				$sum[$row['kind']][$row['classify_code']] = $row['price'];
				$sum[$row['kind']]['total']+=$row['price'];
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$return = array(
			'count'=>$rs['count'],
			'sum'=>$sum,
			'list'=>$list,
			'paging'=>$paging
		);

		if($this->output=='excel') {
			return $return;
		}
		else {
			if($rs['count']['search']>0) {
				return_json(true, '', $return);
			}
			else {
				return_json(false, '', $return);
			}
		}
	}

	public function excel() {
		$p = $this->input->get(NULL, true);
		$type = $p['type'];

		$this->param['limit'] = $p['limit'];
		$this->output="excel";
		if($type == 'goods') {
			$list = $this->goods_list_paging($p);
			$tpl = "/stock/goods_excel.html";
			$filename = "입출고내역_".date('Ymd').".xls";
		}
		else {
			$list = $this->inout_list_paging($p);
			$tpl = "/stock/inout_excel.html";
			$filename = "물품목록_".date('Ymd').".xls";
		}

		$this->load->dbutil();
		// $this->load->helper('file');
		$this->load->helper('download');

		$body = $this->layout_lib->fetch_($tpl, $list);
		force_download($filename, $body);
	}





	// 20170405 kruddo : 재료비 분류설정
	public function cost() {
		$group = $this->uri->segment(3);
		$datum = array(
			'cfg'=>array(
				'group'=>$group
			)
		);
		$this->_render('cost', $datum );
	}


	public function cost_input() {
		$kind = $this->input->post('kind');
		$no = $this->input->post('no');
		$code_parent = $this->input->post('code_parent');

		if($no) {
			$mode  = 'update';
			$rs = $this->Stock_Model->select_category_row(array('no'=>$no));
		}
		else {
			$mode = 'insert';
			$rs = array(
				'kind'=>$kind,
				'code_parent'=>$code_parent,  //병원코드(000H), 총무팀코드(000C),			 과목(000S) 20170404 kruddo - 추가
				'is_use'=>'Y'
			);
		}

		$datum = array(
			'cfg'=>array(
				'mode'=>$mode
			),
			'rs'=>$rs
		);
		$this->_display('cost_input', $datum );
	}

	public function item_sub_input() {

		$no = $this->input->post('no');
		if($no) {
			$mode  = 'update';
			$rs = $this->Stock_Model->select_category_row(array('no'=>$no));
		}
		else {
			$mode = 'insert';
			$rs = array(
				'kind'=>'item_sub',
				'code_parent'=>$this->param['code_parent'],
				'is_use'=>'Y'
			);
		}

		//분류리스트
		$categories = $this->Stock_Model->select_category(array('kind'=>'item'), 'name, code, CONCAT(code_parent,"_",code) AS code_real', 'code_real');
		$datum = array(
			'cfg'=>array(
				'mode'=>$mode,
				'categories'=>$categories
			),
			'rs'=>$rs
		);

		//return_json(true, '', $datum);

		$this->_display('cost_product_input', $datum );
		//$this->_render('cost_product_input', $datum );
	}


	public function category_sub_list() {
		//$where = $this->param;
		$categories = $this->Stock_Model->select_category(array('is_use'=>'Y'), 'name, code, CONCAT(code_parent,"_",code) AS code_real', 'code_real');

		$code_parent = $this->input->post('code_parent');
		$where = array('code_parent'=>$code_parent);

		$fields = 'p.no as pno, p.code_parent pcode_parent, g.no as gno, g.goods_name, g.unit_price as goods_unit_price, concat(group_code,"_",classify_code) as c_code, concat(group_code,"_",classify_code,"_",item_code) as item_code';
		//, @RNUM := @RNUM + 1 AS rownum

		$rs = $this->Stock_Model->select_category_sub($where, $fields);
		$idx = 1;
		$list = array();
		foreach($rs as $row) {


			$row['classify_name'] = $categories[$row['c_code']]['name'];
			$row['item_name'] = $categories[$row['item_code']]['name'];
			$row['category_name'] = $category_subject[$row['category_no']]['name'];

			$row['rownum'] = $idx;
			$list[] = $row;
			$idx++;
		}

		//$datum = array(
		//	'list'=>$list,
		//	'categories'=>$categories
		//	);




		if($rs) {
			return_json(true, '', $list);
		}
		else {
			return_json(false,'');
		}
	}
	// 20170405 kruddo : 재료비 분류설정

	/**
	 * 구매관리
	 * @return [type] [description]
	 */
	public function buy_lists() {

		$this->load->model('User_model');
		$status = $this->config->item('order_status');
		unset($status['cancel']);

		$team_list = $this->User_model->get_team_list(array('50','60')); //사용팀(의료지원실)

		unset($team_list[53]); //회복팀제외
		unset($team_list[52]); //콜센터제외


		$datum = array(
			'cfg'=>array(
				'date'=>$this->common_lib->get_cfg(array('date')),
				'status'=>$status,
				'team'=>$team_list,
			)
		);

		$this->_render('buy_lists', $datum);
	}

	public function buy_lists_inner() {
		$p = $this->param;
		$this->load->model('User_model');

		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		parse_str($this->param['search'], $assoc);
		$where = array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			$k = str_replace(';','',$k);
			if($v == 'all' || (!$v && $v!=='0')) continue;


			switch($k) {
				case 'sw':
					//$where["( client_name LIKE '%{$v}%' )"]=NULL;
					break;
				case 'status':
					$where['status']="{$v}";
					break;
				case 'date_s':
					$where['date_insert >=']="{$v} 00:00:00";
					break;
				case 'date_e':
					$where['date_insert <=']="{$v} 23:59:59";
					break;
				default :
					//$where[$k] = $v;
					break;
			}
		}

		$user_id = $this->session->userdata('ss_user_id');
		$where_offset = array('is_delete'=>'N', 'status != '=>'cancel');

		$status = $this->config->item('order_status');
		$team_list = $this->User_model->get_team_list(array()); //사용팀(의료지원실)


		$rs = $this->Stock_Model->select_order_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;
				$row['tel'] = format_mobile($row['tel']);
				$row['status_text'] = $status[$row['status']];
				$row['team_name'] = $team_list[$row['user_team']];
				$list[] = $row;
				$idx--;
			}
		}


		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page,
			'url_type'=>'javascript',
			'url'=>'OrderLists.load'
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);
		$this->_render('buy_lists.inner', $return, 'inc');
	}

	public function buy_view() {
		$order_no = $this->input->post('no', true);

		//발주서확인처리
		$this->Stock_Model->update_order(array('status'=>"view"), array('no'=>$order_no, 'status'=>'wait'));
		$order_info = $this->Stock_Model->select_order_row(array('no'=>$order_no));
		$order_goods = $this->Stock_Model->select_order_goods(array('order_no'=>$order_no));

		//카테고리정보
		$categories = $this->Stock_Model->select_category(array('code_parent like "000H%"'=>null),'CONCAT(code_parent,"_",code) AS id, name', 'id');

		//거래처정보
		$client = $this->Stock_Model->select_client_all();
		//pre($order_info);
		foreach($order_goods as $row) {
			$item_id = implode('_',array($row['group_code'], $row['classify_code'], $row['item_code']));
			$classify_id = implode('_',array($row['group_code'], $row['classify_code']));

			$list[$row['classify_code']]['name'] = $categories[$classify_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['count']+=1;
			$list[$row['classify_code']]['children'][$row['item_code']]['name']=$categories[$item_id]['name'];

			if(in_array($order_info['status'],array('adjust','approval'))) $row['client_no'] = $row['client_order'];

			$list[$row['classify_code']]['children'][$row['item_code']]['children'][$row['no']] = $row;
		}

		//echo count($list);
		$assign = array(
			'readonly'=>(in_array($order_info['status'],array('wait','view')))?'':'disabled',
			'mod'=>(count($list)==1)?1:0,
			'client'=>$client,
			'order'=>$order_info,
			'list'=>$list,
			'auth'=>array(
				'stock_adjust'=>$this->common_lib->check_auth_group('stock_adjust')
			)
		);

		//pre($list);
		$this->_render('buy_view', $assign, 'inc');
	}

	/**
	 * 재고변동표
	 * @return [type] [description]
	 */
	public function table() {
		$datum = array();
		$this->_render('table', $datum);
	}

	/**
	 * 재고조정
	 * @return [type] [description]
	 */
	public function change() {
		$datum = array();
		$this->_render('change', $datum);
	}

	/**
	 * 발주신청
	 * @return [type] [description]
	 */
	public function order_form() {
	/*
		$argu = $this->input->post(null, true); //선택상품정보
		//카테고리정보
		$categories = $this->Stock_Model->select_category(array('code_parent like "000H%"'=>null),'CONCAT(code_parent,"_",code) AS id, name', 'id');

		$where = array('group_code'=>'000H', 'goods_name is not null'=>null);

		//팀정보 (54:치료팀,50:피부관리팀,60:간호팀,51:코디팀)인 경우 사용가능물품만 보여준다
		$team_code = $this->session->userdata['ss_team_code'];
		if(in_array($team_code,array('54','50','60','51'))) {
			$where["find_in_set({$team_code}, use_team)"] = null;
		}

		//유효물품정보
		$goods = $this->Stock_Model->select_goods($where);
		foreach($goods as $row) {
			$item_id = implode('_',array($row['group_code'], $row['classify_code'], $row['item_code']));
			$classify_id = implode('_',array($row['group_code'], $row['classify_code']));

			$row['value'] = $argu['goods'][$row['no']];
			//echo $id."<Br />";
			$list[$row['classify_code']]['name'] = $categories[$classify_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['count']+=1;
			$list[$row['classify_code']]['children'][$row['item_code']]['name']=$categories[$item_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['children'][$row['no']] = $row;
		}

		$datum = array(
			'list'=>$list
		);

		$layout = ($argu['layout'])?$argu['layout']:'default';
		*/
		$datum = array();
		$this->_render('order_form', $datum);
	}

	public function order_form_choice() {
		$argu = $this->input->post(null, true); //선택상품정보
		//카테고리정보
		$categories = $this->Stock_Model->select_category(array('code_parent like "000H%"'=>null),'CONCAT(code_parent,"_",code) AS id, name', 'id');

		$where = array('group_code'=>'000H', 'goods_name is not null'=>null);

		//팀정보 (54:치료팀,50:피부관리팀,60:간호팀,51:코디팀)인 경우 사용가능물품만 보여준다
		$team_code = $this->session->userdata['ss_team_code'];
		if(in_array($team_code,array('54','50','60','51'))) {
			$where["find_in_set({$team_code}, use_team)"] = null;
		}

		//유효물품정보
		$goods = $this->Stock_Model->select_goods($where);
		foreach($goods as $row) {
			$item_id = implode('_',array($row['group_code'], $row['classify_code'], $row['item_code']));
			$classify_id = implode('_',array($row['group_code'], $row['classify_code']));

			$row['value'] = $argu['goods'][$row['no']];
			//echo $id."<Br />";
			$list[$row['classify_code']]['name'] = $categories[$classify_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['count']+=1;
			$list[$row['classify_code']]['children'][$row['item_code']]['name']=$categories[$item_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['children'][$row['no']] = $row;
		}

		$datum = array(
			'list'=>$list
		);

		$this->_render('order_form.choice', $datum, 'inc');
	}

	public function order_form_confirm() {
		$argu = $this->input->post('goods', true); //선택상품정보
		$goods_no = array_keys($argu); //상품id

		//카테고리정보
		$categories = $this->Stock_Model->select_category(array('code_parent like "000H%"'=>null),'CONCAT(code_parent,"_",code) AS id, name', 'id');

		//신청물품정보
		$goods = $this->Stock_Model->select_goods(array('group_code'=>'000H', 'no'=>$goods_no));
		foreach($goods as $k=>$row) {
			$item_id = implode('_',array($row['group_code'], $row['classify_code'], $row['item_code']));
			$classify_id = implode('_',array($row['group_code'], $row['classify_code']));
			$row['cnt'] = $argu[$k];
			//echo $id."<Br />";
			$list[$row['classify_code']]['name'] = $categories[$classify_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['count']+=1;
			$list[$row['classify_code']]['children'][$row['item_code']]['name']=$categories[$item_id]['name'];

			$list[$row['classify_code']]['children'][$row['item_code']]['children'][$row['no']] = $row;
		}

		$datum = array(
			'list'=>$list,
			'value'=>serialize($argu)
		);

		$this->_render('order_form.confirm', $datum, 'inc');

	}



	/**
	 * 발주목록(내가 등록한 발주목록)
	 * @return [type] [description]
	 */
	public function order_lists() {
		$datum = array(
			'cfg'=>array(
				'date'=>$this->common_lib->get_cfg(array('date')),
				'status'=>$this->config->item('order_status'),
			)
		);

		$this->_render('order_lists', $datum);
	}

	public function order_lists_inner() {
		$p = $this->param;

		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		parse_str($this->param['search'], $assoc);
		$where = array('is_delete'=>'N');

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			$k = str_replace(';','',$k);
			if($v == 'all' || (!$v && $v!=='0')) continue;


			switch($k) {
				case 'sw':
					//$where["( client_name LIKE '%{$v}%' )"]=NULL;
					break;
				case 'status':
					$where['status']="{$v}";
					break;
				case 'date_s':
					$where['date_insert >=']="{$v} 00:00:00";
					break;
				case 'date_e':
					$where['date_insert <=']="{$v} 23:59:59";
					break;
				default :
					//$where[$k] = $v;
					break;
			}
		}

		$user_id = $this->session->userdata('ss_user_id');
		$where_offset = array('user_id'=>$user_id);

		$status = $this->config->item('order_status');
		$rs = $this->Stock_Model->select_order_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;
				$row['tel'] = format_mobile($row['tel']);
				$row['status_text'] = $status[$row['status']];
				$list[] = $row;
				$idx--;
			}
		}


		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page,
			'url_type'=>'javascript',
			'url'=>'OrderLists.load'
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);
		$this->_render('order_lists.inner', $return, 'inc');
	}

	/**
	 * 발주내역 확인
	 * @return [type] [description]
	 */
	public function order_view() {
		$order_no = $this->input->post('no', true);
		$order_info = $this->Stock_Model->select_order_row(array('no'=>$order_no));
		$order_goods = $this->Stock_Model->select_order_goods(array('order_no'=>$order_no));

		//카테고리정보
		$categories = $this->Stock_Model->select_category(array('code_parent like "000H%"'=>null),'CONCAT(code_parent,"_",code) AS id, name', 'id');

		foreach($order_goods as $row) {
			$item_id = implode('_',array($row['group_code'], $row['classify_code'], $row['item_code']));
			$classify_id = implode('_',array($row['group_code'], $row['classify_code']));

			$list[$row['classify_code']]['name'] = $categories[$classify_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['count']+=1;
			$list[$row['classify_code']]['children'][$row['item_code']]['name']=$categories[$item_id]['name'];

			$list[$row['classify_code']]['children'][$row['item_code']]['children'][$row['no']] = $row;
		}

		$assign = array(
			'order'=>$order_info,
			'list'=>$list
		);

		$this->_render('order_view', $assign, 'inc');
	}

	/**
	 * 발주승인 요청함
	 * @return [type] [description]
	 */
	public function order_confirm() {
		$this->load->model('User_model');
		$status = $this->config->item('order_status');
		unset($status['wait']);
		unset($status['view']);
		unset($status['cancel']);

		$team_list = $this->User_model->get_team_list(array('50','60')); //사용팀(의료지원실)

		unset($team_list[53]); //회복팀제외
		unset($team_list[52]); //콜센터제외


		$assign = array(
			'cfg'=>array(
				'date'=>$this->common_lib->get_cfg(array('date')),
				'status'=>$status,
				'team'=>$team_list,
			)
		);

		$this->_render('order_confirm', $assign);
	}

	public function order_confirm_inner() {
		$p = $this->param;
		$this->load->model('User_model');

		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		parse_str($this->param['search'], $assoc);
		$where = array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page'))) continue;
			$k = str_replace(';','',$k);
			if($v == 'all' || (!$v && $v!=='0')) continue;


			switch($k) {
				case 'sw':
					//$where["( client_name LIKE '%{$v}%' )"]=NULL;
					break;
				case 'status':
					$where['status']="{$v}";
					break;
				case 'date_s':
					$where['date_insert >=']="{$v} 00:00:00";
					break;
				case 'date_e':
					$where['date_insert <=']="{$v} 23:59:59";
					break;
				default :
					//$where[$k] = $v;
					break;
			}
		}

		$user_id = $this->session->userdata('ss_user_id');
		$where_offset = array('is_delete'=>'N', 'status'=>array('adjust','approval','disapproval'));

		$status = $this->config->item('order_status');
		$team_list = $this->User_model->get_team_list(array()); //사용팀(의료지원실)


		$rs = $this->Stock_Model->select_order_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;
				$row['tel'] = format_mobile($row['tel']);
				$row['status_text'] = $status[$row['status']];
				$row['team_name'] = $team_list[$row['user_team']];
				$list[] = $row;
				$idx--;
			}
		}


		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page,
			'url_type'=>'javascript',
			'url'=>'OrderLists.load'
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);
		$this->_render('order_confirm.inner', $return, 'inc');
	}

	/**
	 * 발주서승인용 발주내용보기
	 **/
	public function order_confirm_view() {
		$order_no = $this->input->post('no', true);

		//발주서확인처리
		$this->Stock_Model->update_order(array('status'=>"view"), array('no'=>$order_no, 'status'=>'wait'));
		$order_info = $this->Stock_Model->select_order_row(array('no'=>$order_no));
		$order_goods = $this->Stock_Model->select_order_goods(array('order_no'=>$order_no));

		//카테고리정보
		$categories = $this->Stock_Model->select_category(array('code_parent like "000H%"'=>null),'CONCAT(code_parent,"_",code) AS id, name', 'id');

		//거래처정보
		$client = $this->Stock_Model->select_client_all(array(),'*','no');
		//pre($client);
		//
		$price_total = 0;
		foreach($order_goods as $row) {
			$item_id = implode('_',array($row['group_code'], $row['classify_code'], $row['item_code']));
			$classify_id = implode('_',array($row['group_code'], $row['classify_code']));

			$list[$row['classify_code']]['name'] = $categories[$classify_id]['name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['count']+=1;
			$list[$row['classify_code']]['children'][$row['item_code']]['name']=$categories[$item_id]['name'];

			$row['price_sum'] = $row['unit_price']*$row['cnt_order'];
			$price_total+=$row['price_sum'];
			$row['client_name'] = $client[$row['client_no']]['client_name'];
			$list[$row['classify_code']]['children'][$row['item_code']]['children'][$row['no']] = $row;
		}

		//pre($list);
		$assign = array(
			'mod'=>(count($list)==1)?1:0,
			'order'=>$order_info,
			'list'=>$list,
			'price_total'=>$price_total,
			'auth'=>array(
				'stock_approval'=>$this->common_lib->check_auth_group('stock_approval')
			)
		);

		//pre($list);
		$this->_render('order_confirm.view', $assign, 'inc');
	}

	/**
	 * 입고입력내역
	 * @return [type] [description]
	 */
	public function enter_lists() {
		$datum = array();
		$this->_render('enter_lists', $datum);
	}

	/**
	 * 재고조회
	 * @return [type] [description]
	 */
	public function search() {
		$datum = array();
		$this->_render('search', $datum);
	}

	public function client() {
		$datum = array();
		$this->_display('client_lists', $datum);
	}

	public function client_lists_inner() {

		$p = $this->param;

		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		parse_str($this->param['search'], $assoc);
		foreach($assoc as $k=>$v) {

			if(in_array($k, array('page'))) continue;
			$k = str_replace(';','',$k);
			if($v == 'all' || (!$v && $v!=='0')) continue;
			switch($k) {
				case 'sw':
					$where["( client_name LIKE '%{$v}%' )"]=NULL;
				break;
				default :
					//$where[$k] = $v;
					break;
			}
		}

		$where_offset = array();

		$rs = $this->Stock_Model->select_client_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;
				$row['tel'] = format_mobile($row['tel']);
				$list[] = $row;
				$idx--;
			}
		}
		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page,
			'url_type'=>'javascript',
			'url'=>'ClientLists.load'
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		//pre($return);



		///$datum = array();
		$this->_render('client_lists.inner', $return, 'inc');
	}

	public function client_input() {
		$param = $this->input->post(NULL, true);
		$no = $param['no'];
		if($no) {
			$mode = 'update';
			$row = $this->Stock_Model->select_client_row(array('no'=>$no));
		}
		else {
			$mode = 'insert';
			$row = array();
		}
		$datum = array(
			'mode'=>$mode,
			'row'=>$row
		);

		$this->_render('client_input', $datum, 'inc');
	}

	/**
	 * 거래처원장
	 * @return [type] [description]
	 */
	public function client_ledger() {
		$assign = array();
		$this->_render('client_ledger', $assign);
	}



	/**
	 * 뷰
	 * @param  string $tmpl 템플릿경로
	 * @param  array $datum 데이터셋
	 * @return void
	 */
	private function _display($tmpl, $datum) {
		$this->load->view('/stock/'.$tmpl, $datum );
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "stock/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
