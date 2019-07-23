<?php
/**
 * api
 * @author 이혜진
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Api extends CI_Controller {

	function index() {
		echo 'index';
	}

	function category() {
		$valid = $this->_check($_GET);
		if(!$valid) return false;
		$category = $this->config->item('category');
		echo json_encode($category);
	}


	function CPA() {
		$p = $this->input->post(NULL, true);
		$valid = $this->_check($p);
		if(!$valid) return false;


		if($p['age']) {
			$birth = (date('Y')-$p['age']+1).'0000';
		}
		else $birth = '00000000';

		$browser = get_browser($p['agent'], true);
		$record = array(
			'biz_id'=>'HBPS',
			'hst_code'=>'H000',
			'name'=>$p['name'],
			'birth'=>$birth,
			'tel'=>$p['tel'],
			'sex'=>$p['sex'],
			'messenger'=>$p['messenger'],
			'category'=>$p['category'],
			'call_time'=>$p['call_time'],
			'path'=>'L', //유입경로
			'type'=>$p['type'],
			'media'=>$p['media_code'],
			'reg_date'=>date('YmdHis'),
			'memo'=>$p['memo'],
			'referer_url'=>$p['referer_url'],
			'request_url'=>'',
			'os'=>$browser['platform'],
			'browser'=>$browser['browser'],
			'version'=>$browser['version'],
			'ip'=>$p['ip'],
			'date_insert'=>date('Y-m-d H:i:s'),
			'etc'=>$p['etc']
		);

		if($p['biz_id']) {
			$record['biz_id'] = $p['biz_id'];
		}

		$this->load->model('Consulting_Model');
		$rs = $this->Consulting_Model->db_insert($record);
		if($rs) {
			$db_seqno = $this->db->insert_id();
			return_json(true,'',array('no'=>$db_seqno));
		}
		else {
			return_json(false);
		}
	}

	function _send_first($record) {
		$url = "http://easyfirst.co.kr/api/cpa";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $record);
		curl_setopt($ch, CURLOPT_HEADER, TRUE );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1000);


		$response = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		curl_close($ch);

		$rs = json_decode($body, true);
		return $rs;
	}

	function _check($p) {
		$valid_key = array(
			md5('hbps'.date('Ymd'))
		);

		return (in_array($p['id'], $valid_key))?true:false;
	}
}
