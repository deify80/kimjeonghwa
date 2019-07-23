<?php
/**
 * 진료관리 Process
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Finance_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();
		
		$this->param = $this->input->post(NULL, true);
		$this->load->model(array('Support_Model','Manage_Model'));
		
	}

	public function account_save() {
		$p = $this->param;

		$record = array(
			'account_name'=>$p['account_name'],
			'use_flag'=>$p['use_flag'],
			'biz_id'=>$p['biz_id'],
		);

		if($p['mode'] == 'insert') {
			$record = array_merge(array(
				'hst_code'=>$p['hst_code'],
				'biz_id'=>$p['biz_id'],
				'account_type'=>$p['account_type'],
				'bank_code'=>$p['bank_code'],
				'balance'=>str_replace(',','',$p['balance']),
				'account_no'=>$p['account_no'],
				'balance_price'=>str_replace(',','',$p['balance']),
				//'currency'=>$p['currency'],
				'date_insert'=>NOW,
				'date_update'=>NOW
			), $record); 

			$rs = $this->Support_Model->insert_account($record);
		}
		else {
			$where = array(
				'account_seqno'=>$p['account_seqno']
			);
			$rs = $this->Support_Model->update_account($record, $where);
		}

		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	public function category_save() {
		$p = $this->param;

		$record = array(
			'group_code'=>$p['group_code'],
			'title'=>$p['title'],
			'use_flag'=>$p['use_flag']
		);

		if($p['mode'] == 'insert') {

			$max = $this->Manage_Model->select_max(array('group_code'=>$p['group_code']), 'code', 'code_item');
			list($gc,$c) = explode('-',$max['code']);
			
			$code = $p['group_code'].'-'.str_pad(($c+1),'3','0',STR_PAD_LEFT);
			$record['code'] = $code;
			$record['biz_id'] = $this->session->userdata('ss_biz_id');
			$record['hst_code'] = $this->session->userdata('ss_hst_code');

			if($p['kind'] == 'item') {
				$record['depth'] = 1;
				$record['parent_code'] = $p['parent_code'];
			}
			else {
				$record['depth'] = 0;	
			}
			
			$rs = $this->Manage_Model->insert_code_item($record);
		}
		else {
			$code = $p['code'];
			$rs = $this->Manage_Model->update_code_item($record, array('code'=>$code));
		}

		if($rs) {
			$classify_code = ($p['kind'] == 'item')?$p['parent_code']:$code;
			return_json(true,'저장되었습니다.', array('code'=>$classify_code));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	public function category_use() {
		$p = $this->param;
		$record = array('use_flag'=>$p['use_flag']);
		$rs = $this->Manage_Model->update_code_item($record, array('code'=>$p['code']));
		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	public function inout_save() {
		$p = $this->param;
		$amount = str_replace(',','',$p['amount']);
		if($p['statement_type']=='O') $amount = $amount*(-1);

		//$balance_price = $p['balance_price']+$amount;

		$query = $this->db->query("SELECT balance_price from finance_account where account_seqno=".$p['account_seqno']);
		$rs = $query->result_array();
		//$balance_price = $rs['balance_price']+$amount;
		foreach($rs as $row){
			$balance_price = $row['balance_price']+$amount;
		}


		$record = array(
			'biz_id'=>$p['biz_id'],
			'classify_code'=>$p['classify_code'],
			'item_code'=>$p['item_code'],
			'plan_date'=>$p['plan_date'],
			'trade_customer'=>$p['trade_customer'],
			'statement_type'=>$p['statement_type'],
			'amount'=>$amount,
			'process_category'=>$p['process_category'],
			'info'=>htmlspecialchars($p['info'], ENT_QUOTES),
			'account_seqno'=>$p['account_seqno'],
			'account_info'=>serialize($p['account']),
			'balance_price'=>$balance_price
		);

		if($p['mode'] == 'insert') {
			$record['reg_user_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Support_Model->insert_finance($record);
		}
		else {
			$record['mod_user_id'] = $this->session->userdata('ss_user_id');
			$rs = $this->Support_Model->update_finance($record, array('seqno'=>$p['seqno']));
		}

		$rs = $this->Support_Model->update_account(array('balance_price'=>$balance_price), array('account_seqno'=>$p['account_seqno']));

		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	public function inout_sign() {
		$p = $this->param;
		$record = array(
			'status'=>$p['status']
		);

		$rs = $this->Support_Model->update_finance($record, array('seqno'=>$p['seqno']));

		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 입출금 삭제
	 * @return [type] [description]
	 */
	function remove_inout() {
		$p = $this->param;
		$inout_no = $this->param['inout_no'];
		$record = array(
			'is_delete'=>'Y'
		);

		$rs = $this->Support_Model->update_finance($record, array('seqno'=>$inout_no));
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function calc_date() {
		$year = $this->param['year'];
		$month = $this->param['month'];

		$cfg_days = $this->config->item( 'yoil' );

		$mk_start = mktime(0,0,0,$month, 1,$year);
		$mk_start_w = date('w',$mk_start);
		$mk_end = mktime(0,0,0,$month, date('t',$mk_start) ,$year);
		
	
		$week_idx=1;
		$weeks_mk[$week_idx]['start'] = ($mk_start-($mk_start_w-1)*60*60*24);
		for($mk = $mk_start;$mk<=$mk_end;$mk+=86400) {
			$d = date('j', $mk);
			$w = date('w',$mk);
			$days[$d] = array(
				'd'=>date('d',$mk),
				'date'=>date('Y-m-d',$mk),
				'w'=>$w,
				'w_name'=>$cfg_days[$w]
			);

			if($w == 1) {
				$weeks_mk[$week_idx]['start'] = $mk;
			}
			else if($w == 0) {
				$weeks_mk[$week_idx]['end'] = $mk;
				$week_idx++;
			}
		}
		$weeks_mk[$week_idx]['end'] = $mk+((6-$w)*60*60*24);
		
		$weeks = array();
		foreach($weeks_mk as $k=>$v) {
			$weeks[$k] = array(
				'start'=>date('Y-m-d', $v['start']),
				'end'=>date('Y-m-d', $v['end'])
			);
		}

		$return = array(
			'month'=>array(
				'start'=>date('Y-m-d', $mk_start),
				'end'=>date('Y-m-d', $mk_end)
			),
			'days'=>$days,
			'weeks'=>$weeks
		);
		return_json(true, '', $return);
	}


	////////////////////////////////// 20170328 kruddo - 회계팀 카드정보 추가
	public function card_save() {
		$p = $this->param;

		$record = array(
			//'card_name'=>$p['card_name'],
			'use_flag'=>$p['use_flag'],
			'biz_id'=>$p['biz_id'],
			'bank_code'=>$p['bank_code'],
			'card_expire_date'=>$p['card_expire_date1'].'/'.$p['card_expire_date2'],
			'account_name'=>$p['account_name'],
			'card_number'=>$p['card_number'],
			'card_kind_code'=>$p['card_kind_code'],
			'card_payment_date'=>$p['card_payment_date'],
			'account_seqno'=>$p['account_seqno'],
			'card_account_seq'=>$p['card_account_seq'],

			'card_amount_limit'=>str_replace(',','',$p['card_amount_limit']),
		);

		if($p['mode'] == 'insert') {
			$record = array_merge(array(
				'hst_code'=>$p['hst_code'],
				'biz_id'=>$p['biz_id'],
				'bank_code'=>$p['bank_code'],
				'card_expire_date'=>$p['card_expire_date1'].'/'.$p['card_expire_date2'],
				'account_name'=>$p['account_name'],
				'card_number'=>$p['card_number'],
				'card_kind_code'=>$p['card_kind_code'],
				'card_payment_date'=>$p['card_payment_date'],
				'account_seqno'=>$p['account_seqno'],

				'card_amount_limit'=>str_replace(',','',$p['card_amount_limit']),
				'card_account_seq'=>$p['card_account_seq'],
				'account_type'=>'D',

				//'currency'=>$p['currency'],
				'date_insert'=>NOW,
				'date_update'=>NOW
			), $record); 

			$rs = $this->Support_Model->insert_account($record);
		}
		else {
			$where = array(
				'account_seqno'=>$p['account_seqno']
			);
			$rs = $this->Support_Model->update_account($record, $where);
		}

		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}
	////////////////////////////////// 20170328 kruddo - 회계팀 카드정보 추가
}
