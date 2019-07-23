<?php
/**
 * 작성 : 2014.12.17
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Bbs extends CI_Controller {
	var $bbs_config = null;
	var $skin_mode = true;



	public function __construct() {
		parent::__construct();

		$this->load->model( array (
				'Bbs_model',
				'Manage_model'
		) );

		$this->type_list = array (
				'일반글(전체공개)',
				'비밀글(대표이사)'
		);
	}



	public function _remap($method) {
		$this->segs = $this->uri->segment_array();

		if ($this->session->userdata( 'ss_hst_code' ) == '') {
			$hst_code = $this->Manage_model->get_hst_code();

			$data = array (
					'ss_hst_code'=>$hst_code
			);
			$this->session->set_userdata( $data );
		}

		$bbs_code = $this->segs[2];
		$this->_get_config( $bbs_code );

		$this->Bbs_model->bbs_code = $bbs_code;

		if (method_exists( $this, $method )) {
			$data = $this->$method();
		}

		$this->yield = TRUE;
		$this->layout = $this->bbs_config['layout'];

		$this->data = array (
				'bbs_code'=>$bbs_code,
				'type_list'=>$this->type_list,
				'bbs_config'=>$this->bbs_config
		);

		if (is_array( $data )) $this->data = array_merge( $this->data, $data );
		if ($this->skin_mode === true) $this->load->view( 'bbs/' . $this->bbs_config['skin'] . '/' . $method, $this->data );
	}



	private function _get_config($bbs_code) {
		$this->bbs_config = $this->Bbs_model->get_config( $bbs_code );
	}



	public function main() {}



	public function lists() {
		$this->skin_mode = false;

		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];

		$first = ($page - 1) * $limit;

		if (! empty( $_GET['srch_type'] ) && ! empty( $_GET['srch_keyword'] )) $where[] = $_GET['srch_type'] . " like '%" . $_GET['srch_keyword'] . "%'";

		$result = $this->Bbs_model->get_list( $where, $first, $limit, $sidx, $sord );
		$total = $this->Bbs_model->get_total();

		foreach ( $result as $i => $row ) {
			$no = ($row['notice_flag'] == 'Y') ? '공지' : $total - $first - $i;

			$icon[$i]->secret = ($row['type'] == '1') ? '<span class="ui-icon ui-icon-locked"></span>' : '';
			$icon[$i]->file = ($row['file'] != '') ? '<span class="ui-icon ui-icon-disk"></span>' : '';

			if($row['status'] == 'done') {
				$row['title'] = '<span style="color:#CFCFCF"><s>'.$row['title'].'</s></span>';
			}
			$list->rows[$i]['id'] = $row['bbs_seqno'];
			$list->rows[$i]['cell'] = array (
					$no,
					'<span style="float:left;">'.$row['title'].'</span>' . $icon[$i]->secret . $icon[$i]->file,
					set_long_date_format( '-', $row['reg_date'] ),
					$row['reg_user_name'],
					$row['hit'],
					$row['bbs_seqno'],
					$row['type']
			);
		}

		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		echo json_encode( $list );
	}



	private function _set_captcha() {
		$this->load->helper( 'captcha' );

		$data = array (
				'img_path'=>'./images/captcha/',
				'img_url'=>'http://' . $_SERVER['SERVER_NAME'] . '/images/captcha/',
				'img_width'=>'150',
				'img_height'=>50,
				'expiration'=>7200,
				'font_path'=>'/lib/fonts/NanumGothicExtraBold.ttf'
		);

		$captcha = create_captcha( $data );

		$input = array (
				'captcha_time'=>$captcha['time'],
				'ip'=>$this->input->ip_address(),
				'word'=>$captcha['word']
		);

		$this->Manage_model->insert( 'captcha', $input );

		return $captcha;
	}



	public function input() {
		if ($this->bbs_config['write_level'] == 0) {
			$captcha = $this->_set_captcha();
		}
		$bbs_seqno = $this->segs[3];

		$mode = 'ADD';
		$row['notice_flag_chk']['N'] = 'checked';
		if (! empty( $bbs_seqno )) {
			$row = $this->Bbs_model->get_info( $bbs_seqno );

			// 20170222 kruddo : 설정에서 bbs_delete에 지정된 그룹 삭제/수정 권한 부여
			$bbs_delete = $this->common_lib->check_auth_group('bbs_delete');
			if ($row['reg_user_id'] != $this->session->userdata( 'ss_user_id' ) && !$bbs_delete) {
				alert( '정상적인 경로가 아닙니다.' );
			}

			$row['notice_flag_chk'][$row['notice_flag']] = 'checked';
			$file_list = $this->_set_file_list( $bbs_seqno );

			$mode = 'MODIFY';
		}

		$data = array (
				'captcha'=>$captcha,
				'row'=>$row,
				'file_list'=>$file_list,
				'mode'=>$mode
		);

		return $data;
	}



	public function view() {
		$this->load->library( 'encrypt' );
		$this->load->helper( 'pseudocrypt' );

		$bbs_seqno = $this->segs[3];

		// 해당 정보 추출
		$row = $this->Bbs_model->get_info( $bbs_seqno );
		$row['reg_date'] = set_long_date_format( '-', $row['reg_date'] );

		if ($this->session->userdata( 'ss_duty_code' ) != '9' && $row['type'] == '1') {
			if ($row['passwd'] != md5( base64_decode( $this->segs[4] ) )) {
				alert( '정상적인 경로가 아닙니다.' );
			}
		}

		$this->Bbs_model->update_hit( $bbs_seqno );

		$comment_list = $this->_set_comment_list( $bbs_seqno );
		$file_list = $this->_set_file_list( $bbs_seqno );
		$neighbor_list = $this->_get_neighbor( $bbs_seqno );

		$data = array (
				'row'=>$row,
				'comment_list'=>$comment_list,
				'file_list'=>$file_list,
				'neighbor_list'=>$neighbor_list,
				'auth'=>array(
 					'bbs_delete'=>$this->common_lib->check_auth_group('bbs_delete')				// 20170222 kruddo : 게시판 수정/삭제 권한
  				),
		);

		return $data;
	}



	private function _set_comment_list($bbs_seqno) {
		$result = $this->Bbs_model->get_comment_list( $bbs_seqno );
		foreach ( $result as $i => $comment_row ) {
			$comment_list[$i]->reg_date = set_long_date_format( '-', $comment_row['reg_date'] );
			$comment_list[$i]->reg_user_name = $comment_row['reg_user_name'];
			$comment_list[$i]->comment = $comment_row['comment'];
		}

		return $comment_list;
	}



	private function _set_file_list($bbs_seqno) {
		$result = $this->Bbs_model->get_file_list( $bbs_seqno );
		foreach ( $result as $i => $file_row ) {

			$ext[$i] = end( explode( '.', $file_row['new_name'] ) );

			$file_list[$i]->seqno = $file_row['seqno'];
			$file_list[$i]->file_name = $file_row['file_name'];
			$file_list[$i]->new_name = $file_row['new_name'];
			$file_list[$i]->ext = $ext[$i];
		}
		return $file_list;
	}



	private function _get_neighbor($bbs_seqno) {
		$list['prev'] = $this->Bbs_model->get_neighbor( 'PREV', $bbs_seqno );
		$list['next'] = $this->Bbs_model->get_neighbor( 'NEXT', $bbs_seqno );

		return $list;
	}
}

