<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report_lib {
	private $ci;

	function __construct() {
		$this->ci =& get_instance();
		$this->sign_status = array(
			'wait'=>'대기',
			'view'=>'확인',
			'approved'=>'승인',
			'rejected'=>'반려'
		);
		$this->report_status = array(
			'wait'=>'대기',
			'ing'=>'결재진행중',
			'approved'=>'승인',
			'rejected'=>'반려',
			'cancel'=>'상신취소'
		);

		$this->ci->load->model('Report_Model');
	}

	function get_sign_status($report_no) {
		$where = array('r.no'=>$report_no);
		$row = $this->ci->Report_Model->select_report_row($where);
		$waiter_info = unserialize($row['waiter_info']);
		$waiter_info['status'] = $this->sign_status[$row['sign_status']];
		return $waiter_info;	
	}

	function sign($report_no, $sign) {
		$user_id = $this->ci->session->userdata('ss_user_id');
		$sign_row = $this->ci->Report_Model->select_sign_row(array('report_no'=>$report_no, 'user_id'=>$user_id, 'status'=>'view'));
		if(!$sign_row) return false;

		$rs = $this->ci->Report_Model->update(array('status'=>$sign), array('no'=>$sign_row['no']), 'report_sign'); //결재값 업데이트
		if(!$rs) return flase;

		$this->ci->db->trans_begin();//트랜잭션시작

		$next = $this->ci->Report_Model->select_sign_row(array('status'=>'assign', 'report_no'=>$report_no, 'type'=>'approval'), 'step ASC');
		switch ($sign) {
			case 'approved':
				//다음결재자 존재하는경우
				if($next) {
					$this->ci->Report_Model->update(array('status'=>'wait'), array('no'=>$next['no']), 'report_sign'); //다음 결재자 대기상태로변경
					$this->ci->Report_Model->update(array('sign_no'=>$next['no']), array('no'=>$report_no), 'report'); //결재번호 업데이트
				}
				//마지막 결재자인경우
				else {
					$this->ci->Report_Model->update(array('report_status'=>$sign), array('no'=>$report_no), 'report'); //결재스텝 종료
					//서브결재 종료처리
					$this->ci->load->model('Support_Model');
					$this->ci->Support_Model->update_finance(array('is_delete'=>'N'), array('referer_no'=>$report_no, 'status'=>'approved'));
				}
			break;
			case 'rejected':
				$this->ci->Report_Model->update(array('report_status'=>$sign), array('no'=>$report_no), 'report'); //결재스텝 종료
			break;
		}

		if ($this->ci->db->trans_status() === FALSE) {
    		$this->ci->db->trans_rollback();
    		return false;
		}
		else {
    		$this->ci->db->trans_commit();
    		$return = array(
    			'user_id'=>$user_id,
    			'sign'=>$sign
    		);
    		return $return;
		}
	}

	function view($report_no) {
		$model = $this->ci->Report_Model;
		$user_id = $this->ci->session->userdata('ss_user_id');

		//코멘트 확인처리
		$model->set_update(array('reader_id'=>"CONCAT(reader_id,',','{$user_id}')"), array('report_no'=>$report_no), 'report_comment');
		$sign_row = $model->select_sign_row(array('report_no'=>$report_no, 'user_id'=>$user_id, 'status'=>'wait'));
		if(!$sign_row) return false;

		//문서 확인처리
		$record = array('status'=>'view');
		$rs = $model->update($record, array('no'=>$sign_row['no']), 'report_sign');

		if($rs) {
			if($sign_row['type'] == 'approval') {
				$model->update(array('report_status'=>'ing'), array('no'=>$report_no), 'report');
			}

			return true;
		}
		else {
			return flase;
		}
		
	}
}
?>