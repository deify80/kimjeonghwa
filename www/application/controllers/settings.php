<?php
/**
 * 설정
 * 작성 : 2015.08.26
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Settings extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);


		$this->load->model(array (
			'Manage_Model'
		));
	}

	/**
	 * 미디어코드 설정
	 * @return [type] [description]
	 */
	function media() {

		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y')); //미디어 그룹
		$datum = array(
			'cfg'=>array(
				'date'=>$this->common_lib->get_cfg('date'),
				'media_group'=>$media_group
			),
			'list'=>$list
		);
		$this->_render('/manage/settings/media', $datum);
	}


	function media_inner() {
		$p = $this->param;
		//미디어별 db수
		$this->load->model('Consulting_Model');
		$where = array(
			'biz_id'=>$this->session->userdata('ss_biz_id'),
			'reg_date >= '=>str_replace('-','',$p['sdate_s']).'000000',
			'reg_date <= '=>str_replace('-','',$p['sdate_e']).'235959',
		);

		$count = $this->Consulting_Model->groupby_consulting($where, 'media');
		// echo $this->db->last_query();
		// pre($count);
		$list = array();
		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y')); //미디어 그룹
		foreach($media_group as $key=>$value) {
			$items = $this->common_lib->get_code_item( '04' , $key, 'all');
			foreach($items as $k=>$v) {
				$items[$k]['cnt']=$count[$v['title']]['cnt'];
			}

			$list[$key] = array(
				'name'=>$value,
				'children'=>$items,
				'count'=>count($items)
			);
		}


		$datum = array(
			'list'=>$list
		);


		$this->_render('/manage/settings/media_inner', $datum, 'inc');
	}

	function media_input() {
		$p = $this->param;

		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1)); //미디어 그룹

		if($p['mode'] == 'insert') {
			$mode = 'insert';
			$parent_code = $p['parent_code'];
			if($parent_code) {
				$direct = true;
			}
			else {
				$direct = false;
			}

			$item = array(
				'parent_code'=>$parent_code
			);
		}
		else {
			$mode = 'update';
			$direct = true;
			$item =  $this->Manage_Model->select_code_row(array('code'=>$p['code'])); //진료부위
		}

		// 20170228 kruddo : 미디어코드 > 링크 추가
		$query = $this->db->query("SELECT * FROM code_item_media_link WHERE code='".$p['code']."' order by no");
		$media_link = $query->result_array();
		// 20170228 kruddo : 미디어코드 > 링크 추가


		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'group_code'=>'04',
				'direct'=>$direct,
				'media_group'=>$media_group
			),
			'item'=>$item,
			'media_link_info'=>$media_link,
		);

		$this->_render('/manage/settings/media_input', $datum, 'inc');

		//return_json(true, '', $datum);
	}

	function sms_msg() {
		$this->load->model('sms_model');
		$label = $this->sms_model->select_msg_type(array());

		$datum = array(
			'cfg'=>array(
				'label'=>$label
			)
		);
		$this->_render('/manage/settings/sms_msg', $datum);
	}

	function sms_msg_inner() {
		$this->load->model('sms_model');

		$sms_no = $this->param['no'];
		$where = array('no'=>$sms_no);

		$sms = $this->sms_model->select_msg_row($where);


		$datum = array(
			'row'=>$sms
		);
		$this->_render('/manage/settings/sms_msg_inner', $datum, 'inc');
	}


	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
