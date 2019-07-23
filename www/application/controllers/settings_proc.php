<?php
/**
 * 설정 Process
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Settings_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->load->model(array('Manage_Model'));

	}

	function save_media() {
		$p = $this->param;

		if($p['mode'] == 'insert') {
			$max = $this->Manage_Model->select_max(array('group_code'=>$p['group_code']), 'code', 'code_item');
			list($gc,$c) = explode('-',$max['code']);

			$code = $p['group_code'].'-'.str_pad(($c+1),'3','0',STR_PAD_LEFT);
			$record = array(
				'code'=>$code,
				'title'=>$p['title'],
				'depth'=>'1',
				'hst_code'=>$this->session->userdata('ss_hst_code'),
				'group_code'=>$p['group_code'],
				'use_flag'=>'Y',
				'etc'=>$p['etc']
			);
			if($p['parent_code']) {
				$record['parent_code']=$p['parent_code'];
				$record['depth'] = '2';
			}
			$rs = $this->Manage_Model->insert_code_item($record);
		}
		else {
			$record = array(
				'title'=>$p['title'],
				'etc'=>$p['etc']
			);

			$rs = $this->Manage_Model->update_code_item($record, array('code'=>$p['code']));
		}

		// 20170228 kruddo : 링크 저장
		$i = 1;
		if(!$p['code'])	$p['code'] = $code;
		$where = array(
			'code'=>$p['code']
		);
		$this->db->delete('code_item_media_link',$where);

		foreach($p['link'] as $link){
			if($link != ""){
				if(substr($link, 0, 7) != "http://" && substr($link, 0, 8) != "https://")	$link = "http://".$link;
				$input = array(
					'code'=>$p['code'],
					'no'=>$i,
					'title'=>$p['title'],
					'link'=>$link
				);
				$rs = $this->db->insert( 'code_item_media_link', $input );
				$i++;
			}
		}
		// 20170228 kruddo : 링크 저장


		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function remove_media() {
		$code = $this->param['code'];
		$rs = $this->Manage_Model->update_code_item(array('use_flag'=>'N'), array('code'=>$code));
		//echo $this->db->last_query();
		if($rs) {
			//하위분류 삭제
			$this->Manage_Model->update_code_item(array('use_flag'=>'N'), array('parent_code'=>$code));
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function save_smsmsg() {
		$this->load->model('sms_model');
		$p = $this->param;

		$record = array(
			'is_use'=>$p['is_use'],
			'msg'=>$p['msg'],
			'img_use'=>($p['img_use'])?$p['img_use']:'N'
		);

		$where = array('no'=>$p['no']);

		$rs = $this->sms_model->save_sms($record, $where);
		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function get_smsmsg() {
		$this->load->model('sms_model');
		$sms_no = $this->param['no'];
		$where = array('no'=>$sms_no);
		$sms = $this->sms_model->select_msg_row($where);
		return_json(true,'',$sms);
	}
}
