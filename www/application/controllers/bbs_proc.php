<?php
/**
 * 작성 : 2014.12.22
 * 수정 : 
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Bbs_proc extends CI_Controller {



	public function __construct() {
		parent::__construct();
		$this->load->library( 'upload' );
		$this->load->model( array (
				'Bbs_model',
				'Manage_model' 
		) );
		
		$this->upload_path = './DATA/bbs/';
	}



	public function insert($bbs_code) {
		
		$is_valid = 'Y';
		
		if ($this->input->post( 'captcha' ) != '') {
			$captcha_total = $this->Bbs_model->check_captcha( $this->input->post( 'captcha' ), $this->input->ip_address() );
			
			if ($captcha_total == 0) {
				$msg = '스팸방지코드가 일치하지 않습니다.';
				$is_valid = 'N';
			}
		}
		
		$this->Bbs_model->bbs_code = $bbs_code;
		
		$input = null;
		$input['title'] = $this->input->post( 'title' );
		$input['contents'] = $this->input->post( 'contents' );
		if ($this->input->post( 'passwd' ) != '') $input['passwd'] = md5( $this->input->post( 'passwd' ) );
		$input['ip'] = $this->input->ip_address();
		$input['notice_flag'] = ($this->input->post( 'notice_flag' ) == '')? 'N':$this->input->post( 'notice_flag' );
		$input['type'] = $this->input->post( 'type' );
		
		if ($is_valid == 'Y') {
			
			if ($this->input->post( 'mode' ) == 'ADD' || $this->input->post( 'mode' ) == '') {
				$input['bbs_code'] = $bbs_code;
				$input['hst_code'] = $this->session->userdata( 'ss_hst_code' );
				$input['reg_date'] = TIME_YMDHIS;
				$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
				
				$this->Bbs_model->insert( $input );
				$bbs_seqno = $this->Bbs_model->get_insert_id();
			} else if ($this->input->post( 'mode' ) == 'MODIFY') {
				
				$this->Bbs_model->update( $this->input->post( 'bbs_seqno' ), $input );
				$bbs_seqno = $this->input->post( 'bbs_seqno' );
			}
			
			$msg = '등록 완료했습니다.';
			
			$this->do_upload( $bbs_code, $bbs_seqno );
		}
		
		
		
		$json = null;
		$json['msg'] = $msg;
		$json['is_valid'] = $is_valid;
		echo json_encode( $json );
	}



	function do_upload($bbs_code, $bbs_seqno) {
		$files = $_FILES;
		$count = count( $_FILES['userfile']['name'] );
		for($i = 0; $i < $count; $i ++) {
			
			$_FILES['userfile']['name'] = $files['userfile']['name'][$i];
			$_FILES['userfile']['type'] = $files['userfile']['type'][$i];
			$_FILES['userfile']['tmp_name'] = $files['userfile']['tmp_name'][$i];
			$_FILES['userfile']['error'] = $files['userfile']['error'][$i];
			$_FILES['userfile']['size'] = $files['userfile']['size'][$i];
			
			$this->upload->initialize( $this->_set_upload_options( $bbs_code, $bbs_seqno, $i ) );
			$this->upload->do_upload();
			
			$input = null;
			$input['file_name'] = $files['userfile']['name'][$i];
			$input['file_size'] = $files['userfile']['size'][$i];
			$input['mime_type'] = $files['userfile']['type'][$i];
			$input['new_name'] = $this->upload->file_name;
			$input['bbs_seqno'] = $bbs_seqno;
			
			if ($input['file_size'] > 0) $this->Bbs_model->file_insert( $input );
		}
	}



	private function _set_upload_options($bbs_code, $bbs_seqno, $i) {
		$config = array ();
		$config['upload_path'] = $this->upload_path . $bbs_code;
		$config['allowed_types'] = '*';
		$config['file_name'] = $bbs_seqno . DS . random_string();
		
		return $config;
	}



	public function comment_insert() {
		$input = null;
		$input['reg_user_id'] = set_null( $this->session->userdata( 'ss_user_id' ) );
		$input['reg_date'] = TIME_YMDHIS;
		$input['comment'] = $this->input->post( 'comment' );
		$input['bbs_seqno'] = $this->input->post( 'bbs_seqno' );
		
		$is_success = $this->Bbs_model->comment_insert( $input );
		
		$json = null;
		$json['is_success'] = $is_success;
		$json['reg_date'] = set_long_date_format( '-', $input['reg_date'] );
		$json['reg_user_name'] = $this->session->userdata( 'ss_name' );
		$json['comment'] = $input['comment'];
		
		echo json_encode( $json );
	}



	public function confirm_passwd($bbs_code) {
		$this->load->library( 'encrypt' );
		$this->load->helper( 'pseudocrypt' );
		
		$this->Bbs_model->bbs_code = $bbs_code;
		$row = $this->Bbs_model->get_info( $this->input->post( 'bbs_seqno' ) );
		$is_success = 0;
		$pass = $this->encrypt->encode( $this->input->post( 'passwd' ) );
		
		if (md5( $this->input->post( 'passwd' ) ) == $row['passwd']) {
			$is_success = 1;
			$url = '/bbs_view/' . $bbs_code . '/' . $row['bbs_seqno'] . '/' . base64_encode( $this->input->post( 'passwd' ) ) . '/' . $this->encrypt->encode( $this->input->post( 'passwd' ) );
		}
		
		$json = null;
		$json['is_success'] = $is_success;
		$json['url'] = $url;
		
		echo json_encode( $json );
	}



	public function download($bbs_code, $seqno) {
		$this->load->helper( 'download' );
		
		$row = $this->Bbs_model->get_file_info( $seqno );
		
		$data = file_get_contents( $this->upload_path . $bbs_code . "/" . $row['new_name'] );
		force_download( $row['file_name'], $data );
	}



	public function remove_file() {
		$this->load->helper( 'file' );
		
		// 파일 체크
		$row = $this->Bbs_model->get_file_info( $this->input->post( 'seqno' ) );
		
		if (is_array( $row )) {
			
			// 파일삭제
			@unlink( $this->upload_path . $row['new_name'] );
			$this->Bbs_model->delete_file( $this->input->post( 'bbs_seqno' ), $this->input->post( 'seqno' ) );
		}
	}



	public function remove() {		
		$this->Bbs_model->bbs_code = $this->input->post( 'bbs_code' );
		$this->Bbs_model->delete( $this->input->post( 'bbs_seqno' ) );
	}
}