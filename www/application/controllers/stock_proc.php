<?php
/**
 * 근태관리 Process
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Stock_proc extends CI_Controller {

	private $dir = './DATA/stock/';

	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->load->model(array('Stock_Model'));

		// $this->yield = TRUE;
	}


	function category_save() {
		$mode = $this->input->post('mode');
		$code = $this->input->post('code');
		$code_parent = $this->param['code_parent'];

		$record = array(

			'name'=>$this->param['name'],
			'is_use'=>$this->param['is_use']
		);
		if($mode == 'insert') {
			//분류코드 중복체크
			$count = $this->Stock_Model->count_category(array('code'=>$code, 'code_parent'=>$code_parent));
			if($count>0) {
				return_json(false,'이미 존재하는 분류코드입니다.');
			}

			$kind = $this->input->post('kind');

			$record['kind'] = $kind;
			$record['code_parent'] =$code_parent;
			$record['code'] = $code;
			$record['date_insert'] = NOW;
			$rs = $this->Stock_Model->insert_category($record);
		}
		else {
			$rs = $this->Stock_Model->update_category($record, array('no'=>$this->input->post('no')));
		}

		if($rs) {
			return_json(true, '저장되었습니다.', array('code_real'=>$code_parent.'_'.$code));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function category_use() {
		$no = $this->input->post('no');
		$is_use = $this->input->post('is_use');
		$rs = $this->Stock_Model->update_category(array('is_use'=>$is_use), array('no'=>$no));

		if($rs) {
			//사용안함으로 변경시 하위 항목 상태 일괄변경
			// if($is_use=='N') {
			// 	$classify = $this->Stock_Model->select_category_row(array('no'=>$no), 'kind, code');
			// 	if($classify['kind'] == 'classify') {
			// 		$this->Stock_Model->update_category(array('is_use'=>$is_use), array('code_parent'=>$classify['code']));
			// 	}
			// }
			return_json(true, '변경되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 물품등록
	 * @return [type] [description]
	 */
	function goods_save() {
		// pre($this->param);exit;

		$argu = $this->param;
		$goods_no = $this->param['no'];
		$goods_code = $this->param['goods_code'];
		$goods_name = $this->param['goods_name'];
		if($goods_no > 0) {
			$mode = 'update';
		}
		else {
			$mode = 'insert';
		}

		$record = array(
			'biz_id'=>$this->param['biz_id'],
			'group_code'=>$this->param['group_code'],
			'classify_code'=>$this->param['classify_code'],
			'item_code'=>$this->param['item_code'],
			'use_team'=>(is_array($argu['use_team']))?implode(',',$argu['use_team']):'', //사용팀
			'unit_price'=>str_replace(",","",$this->param['unit_price']),
			'unit_price_vat'=>($this->param['unit_price_vat'])?$this->param['unit_price_vat']:'N', //부가세포함여부
			'unit'=>$this->param['unit'],
			'volume'=>$this->param['volume'],
			'client_no'=>$argu['client_no'], //거래처번호
			'comment'=>htmlspecialchars($this->param['comment'], ENT_QUOTES)
		);
		if($mode == 'insert') {
			//물품코드 중복체크
			$count = $this->Stock_Model->count_goods(array('goods_code'=>$goods_code));
			if($count>0) {
				return_json(false,'이미 존재하는 물품코드입니다.');
			}

			//물품명 중복체크
			$count = $this->Stock_Model->count_goods(array('goods_name'=>$goods_name));
			if($count>0) {
				return_json(false,'이미 존재하는 물품명입니다.');
			}


			$record['goods_code'] = $goods_code;
			$record['goods_name'] = $goods_name;
			$record['date_insert'] = NOW;
			$rs = $goods_no = $this->Stock_Model->insert_goods($record);
		}
		else {
			$count = $this->Stock_Model->count_goods(array('goods_name'=>$goods_name, 'no !='=>$goods_no));
			if($count>0) {
				return_json(false,'이미 존재하는 물품명입니다.');
			}

			$record['goods_name'] = $goods_name;
			$rs = $this->Stock_Model->update_goods($record, array('no'=>$goods_no));
		}

		if($rs) {
			//사업장별 현재고/최소보유수량 입력
			$biz = $this->param['biz'];
			foreach($biz as $biz_id=>$biz_qty) {
				$record = array(
					'goods_no'=>$goods_no,
					'biz_id'=>$biz_id,
					'qty_first'=>str_replace(',','',$biz_qty['qty_first']),
					'qty_stock'=>str_replace(',','',$biz_qty['qty_first']),
					'qty_min'=>$biz_qty['qty_min'],
					'date_insert'=>NOW
				);
				$this->Stock_Model->insert_qty($record);
			}
			return_json(true, '저장되었습니다.');

		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	public function goods_duplicate() {
		$argu = $this->param;
		//pre($argu);

		$val = $argu[$argu['target_id']];
		$where = array($argu['target_id']=>$val);
		if($argu['mode'] == 'update' && $argu['no'] > 0) {
			$where['no !=']=$argu['no'];
		}

		$count = $this->Stock_Model->count_goods($where);
		//echo $this->db->last_query();
		if($count>0) {
			$label = ($argu['target'] == 'goods_name')?'물품명':'물품코드';
			return_json(false,"이미 존재하는 {$label}입니다.");
		}
		else {
			return_json(true,'사용가능합니다.',array('confirm'=>$val));
		}
	}

	function goods_remove() {
		$goods_no = $this->param['goods_no'];
		if($goods_no<1) {
			return_json(false, '필수값 누락');
		}

		$rs = $this->Stock_Model->update_goods(array('is_delete'=>'Y'), array('no'=>$goods_no));
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function inout_save() {

		$goods_no = $this->param['no'];
		$biz_id = $this->param['biz_id'];
		$mode = $this->param['mode'];
		$category_no = $this->param['category_no'];

		//입출력 데이터
		$record = array(
			'kind'=>$this->param['kind'],
			'requester_id'=>$this->param['requester_id'],
			'qty'=>$this->param['qty'],
			'comment'=>htmlspecialchars($this->param['comment'], ENT_QUOTES),
			'date_inout'=>$this->param['date_inout']
		);

		if($mode == 'insert') {
			$record_insert = array(
				'goods_no'=>$goods_no,
				'biz_id'=>$biz_id,
				'writer_id'=>$this->session->userdata('ss_user_id'),
				'date_insert'=>NOW,
				'category_no'=>$category_no
			);
			$record = array_merge($record, $record_insert);
			$rs = $this->Stock_Model->insert_inout($record);
			$inout_no = $rs;
		}
		else {
			$inout_no = $this->param['inout_no'];
			$rs = $this->Stock_Model->update_inout($record, array('no'=>$inout_no));
		}

		if($rs) {
			//재고수량 동기화
			$this->sync_stock($goods_no, $biz_id);
			return_json(true);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function resize_image($config) {
		$this->load->library('image_lib');

		$config_default = array(
			'image_library' => 'gd2',
			'maintain_ratio' => TRUE,
			'create_thumb' => TRUE,
			'thumb_marker' => '_thumb',
			'width' => 150,
			'height' => 150
		);

		$config = array_merge($config_default, $config);

		$this->image_lib->clear();
		$this->image_lib->initialize($config);

		if (!$this->image_lib->resize()) {
			return false;
		}
		else {
			return true;
		}
	}

	function inout_remove() {
		$inout_no = $this->param['inout_no'];

		if($inout_no<1) {
			return_json(false, '필수값 누락');
		}

		$rs = $this->Stock_Model->delete_inout(array('no'=>$inout_no));
		if($rs) {
			$goods_no = $this->param['goods_no'];
			$biz_id = $this->param['biz_id'];
			$this->sync_stock($goods_no, $biz_id);
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function inout_attach_remove() {
		$p = $this->param;

		$rs = $this->common_lib->delete_file($p['file_no']);
		if($rs) {
			$attach_count = $p['attach_count']-1;
			$this->Stock_Model->update_inout(array('attach_count'=>$attach_count), array('no'=>$p['inout_no'])); //첨부파일카운트 업데이트
			return_json(true,'삭제되었습니다.', array('attach_count'=>$attach_count));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function sync_stock($goods_no, $biz_id) {
		$where = array('goods_no'=>$goods_no, 'biz_id'=>$biz_id);
		$first = $this->Stock_Model->select_qty_row($where, 'qty_first');
		if($first) {
			$qty_first = $first['qty_first'];
		}
		else {
			$qty_first = 0;
		}
		$qty_stock = $qty_first;
		$rs = $this->Stock_Model->select_inout_groupby($where, 'sum(qty) AS sum, kind', 'kind');
		foreach($rs  as $kind=>$v) {
			if($kind == 'in') {
				$qty_stock+=$v['sum'];
			}
			else {
				$qty_stock-=$v['sum'];
			}
		}

		$record = array(
			'goods_no'=>$goods_no,
			'biz_id'=>$biz_id,
			'qty_first'=>$qty_first,
			'qty_stock'=>$qty_stock,
			'qty_min'=>'0',
			'date_insert'=>NOW
		);

		$this->Stock_Model->insert_qty($record);
	}

	// 20170406 kruddo - 재료비분류설정
	function cost_product_save(){
		$code_parent = $this->param['code_parent'];
		$checked = $this->param['checked'];

		$count = count( $checked );
		for($i = 0; $i < $count; $i ++) {

			if($checked[$i].trim() != ''){
				$query = $this->db->query("select * from stock_category_product where goods_no='".$checked[$i]."' and code_parent='".$code_parent."'");
				$cnt = $query->num_rows();
				if($cnt < 1){
					$record['code_parent'] = $code_parent;
					$record['goods_no'] = $checked[$i];
					$record['biz_id'] = $this->session->userdata('ss_biz_id');
					$record['date_insert'] = NOW;

					$this->Stock_Model->insert_category_product($record);
				}
			}
		}
		return_json(true, '');
	}

	function cost_product_del(){
		$this->Stock_Model->delete( $this->input->post( 'no' ) );
		return_json(true, '');
	}
	// 20170406 kruddo - 재료비분류설정

	public function client_save() {
		$param = $this->param;
		$record = array(
			'client_name'=>$param['client_name'],
			'manager_name'=>$param['manager_name'],
			'tel'=>$param['tel'],
			'address'=>$param['address'],
			'memo'=>$param['memo'],
			'date_insert'=>NOW,
			'date_update'=>NOW
		);

		if($param['no']) {
			$rs = $this->Stock_Model->update_client($record, array('no'=>$param['no']));
		}
		else {
			$rs = $this->Stock_Model->insert_client($record);
		}

		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else return_json(false,'잠시 후에 다시 시도해주세요.');
	}

	public function client_delete() {
		$param = $this->param;
		$where = array('no'=>$param['no']);
		$rs = $this->Stock_Model->delete_client($where);
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else return_json(false,'잠시 후에 다시 시도해주세요.');
	}

	/**
	 * 발주신청
	 * @return [type] [description]
	 */
	public function order_apply() {
		$argu = $this->param;
		$order_value = unserialize($argu['order_value']);

		$ss = $this->session->userdata;
		$user_id = $ss['ss_user_id'];


		$order_keys = array_keys($order_value);//발주 물품키
		$goods_first = $this->Stock_Model->select_goods_row(array('no'=>$order_keys[0])); //첫번째 발주 물품정보

		$title = $goods_first['goods_name'];
		if(count($order_value)>1) {
			$title.='외 '.(count($order_value)-1).'건';
		}

		$order_code = strtoupper($user_id).'_'.date('YmdHis');

		$record = array(
			'user_id'=>$user_id,
			'user_name'=>$ss['ss_name'],
			'user_team'=>$ss['ss_team_code'],
			'order_code'=>$order_code, //발주서번호
			'status'=>'wait',
			'title'=>$title,
			'comment'=>htmlspecialchars($argu['comment'],ENT_QUOTES),
			'date_insert'=>NOW
		);

		//PRE($record);EXIT;

		$order_no = $this->Stock_Model->insert_order($record);
		if($order_no) {
			$success = true;
			foreach($order_value as $goods_no=>$cnt) {
				$record_goods = array(
					'order_no'=>$order_no,
					'goods_no'=>$goods_no,
					'cnt'=>$cnt,
					'cnt_order'=>$cnt,
					'is_valid'=>'Y'
				);
				$rs = $this->Stock_Model->insert_order_goods($record_goods);
				if(!$rs) $success = false;
			}

			if($success) {
				return_json(true,'발주서가 등록되었습니다.');
			}
			else {
				return_json(false,'잠시 후에 다시 시도해주세요.');
			}
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}

	/**
	 * 발주수량 조정처리(구매관리메뉴)
	 * @return [type] [description]
	 */
	public function order_adjust() {
		$argu = $this->param;

		//발주서 상태변경
		$this->Stock_Model->update_order(array('status'=>$argu['act']), array('no'=>$argu['order_no']));

		//상품수량 및 거래처 정보 수정
		foreach($argu['adjust'] as $goods_no=>$row) {
			$record = array(
				'cnt_order'=>$row['cnt'],
				'client_no'=>$row['client_no']
			);
			$this->Stock_Model->update_order_goods($record, array('order_no'=>$argu['order_no'],'goods_no'=>$goods_no));
		}

		return_json(true,'처리되었습니다.');
	}

	/**
	 * 발주반려
	 * @return [type] [description]
	 */
	public function order_reject() {
		$argu = $this->param;

		//발주서 상태변경(반려처리)
		$rs = $this->Stock_Model->update_order(array('status'=>'reject'), array('no'=>$argu['order_no']));
		if($rs) {
			return_json(true,'발주서가 반려처리되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}

	/**
	 * 발주취소
	 * @return [type] [description]
	 */
	public function order_cancel() {
		$argu = $this->param;
		$order_no = $argu['no'];
		$order_info = $this->Stock_Model->select_order_row(array('no'=>$order_no));
		//TODO 작성자만 취소가능

		$rs = $this->Stock_Model->update_order(array('is_delete'=>'Y'), array('no'=>$order_no));
		if($rs) {
			return_json(true,'발주가 취소 되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
		//pre($argu);
	}

	/**
	 * 발주서 최종처리(승인/반려)
	 */
	public function order_confirm() {
		$argu = $this->param;
		$record = array(
			'status'=>$argu['sign']
		);

		$rs = $this->Stock_Model->update_order($record, array('no'=>$argu['order_no']));

		if($rs) {
			return_json(true,'처리 되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}

	}


	/**
	 * 입고관련 처리 로그
	 */
	private function order_log() {

	}
}
