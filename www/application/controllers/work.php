<?php
/**
 * 작성 : 2015.03.09
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Work extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->load->model( array (
				'Work_model',
				'User_model' ,
				'Business_model'
		) );

		$this->left = 'left3';

		$this->work_status = array (
				'0'=>'대기',
				'1'=>'완료',
				'8'=>'80%(진행중)',
				'6'=>'60%(진행중)',
				'4'=>'40%(진행중)',
				'2'=>'20%(진행중)',
				'X'=>'보류'
		);
		$this->work_type = array (
				'D'=>'일일업무',
				'W'=>'주간업무',
				'CEO'=>'대표님지시사항'
		);
		$this->work_url_type = array (
				'포스팅',
				'이미지',
				'동영상',
				'기타'
		);

		$this->paper_type = array (
				'0'=>'지출보고',
				'1'=>'DB보고',
				'2'=>'인사보고' ,
				'3'=>'소모품사용보고' ,
				'4'=>'물품구매보고' ,
				'5'=>'기타'
		);

		// 그외
		$this->paper_type1 = array (
				'0'=>'지출보고',
				'1'=>'DB보고',
				'2'=>'인사보고'
		);
		// 총무
		$this->paper_type2 = array (
				'3'=>'소모품사용보고' ,
				'4'=>'물품구매보고' ,
				'5'=>'기타'
		);

		$this->complain_type = array('수술결과', '수술비용', '의료진상담', '서비스', '기타');

		$this->doctor_list = $this->config->item( 'doctor' );

		// 20170306 kruddo : 일일업무보고 입력 폼
		$this->aSettleType = array(0=>"일일업무보고",1=>"일일매출보고(상담)",2=>"일일매출보고(코디)",4=>"일일매출보고(피부)");
		$this->aSettleExpenseDoctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));//의사
	}

	public function _remap($method) {
		$this->yield = TRUE;
		$this->segs = $this->uri->segment_array();

		$this->data['work_type'] = $this->work_type;
		$this->data['work_status'] = $this->work_status;
		$this->data['work_url_type'] = $this->work_url_type;

		if (method_exists( $this, '_' . $method )) {
			$view = $this->{'_' . $method}();
			if (! empty( $view )) $this->load->view( $view, $this->data );
		} else {
			$this->$method();
		}
	}

	private function _biz_log() {
		// $this->page_title = '업무현황';
		$work_type = $this->segs[3];

		$team_list = $this->User_model->get_team_list();
		$dept_list = $this->User_model->get_dept_list();

		$search = $_SESSION['search'];
		if(empty($search)) {
			$search = array(
				'dept_code'=>'all',
				'status'=>'all',
				'duty_code'=>'all'
			);
		}

		$datum = array(
			'cfg'=>array(
				'dept'=>$dept_list,
				'work_status'=>$this->work_status,
				'work_type'=>$work_type,
				'team_list'=>$team_list,
				'dept_list'=>$dept_list,
				'menu_idx'=>$this->_set_work_menu( $work_type ),
				'biz'=>$this->session->userdata('ss_biz_list'),
				'duty'=>$this->config->item('duty_code') //$invalid_dept = array('9','2','1');
			),
			'work_type'=>$work_type,
			'search'=>$search
		);

		if($work_type == 'ceo') {
			$this->data = $datum;
			return 'work/biz/ceo';
		}
		else {
			$tpl = ($work_type == 'ceo')?'biz/ceo':'biz/index';
			$this->_render('biz/index', $datum);
		}

		//
	}

	function bizlog_list_paging() {
		$p = $this->param;

		parse_str($this->input->post('search'), $assoc);
		$this->session->set_userdata('search',array_filter($assoc)); //검색데이터 세션처리

		$_SESSION['search'] = $assoc;
		// pre($assoc);

		$type = $p['type']; //share:공동DB
		$page = $assoc['page'];
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;


		//검색조건설정
		$where = array();
		$where_offset = array(
			'w.type'=>$assoc['type']
			// 'u.biz_id' => $this->session->userdata('ss_biz_id'),
			// 'hst_code'=> $this->session->userdata('ss_hst_code')
		);


		if (in_array($this->session->userdata( 'ss_duty_code' ), array(1,8,9))) {
		} else if ($this->session->userdata( 'ss_duty_code' ) == 7) {
			$where_offset['u.team_code'] = $this->session->userdata( 'ss_team_code' );
		} else {
			$where_offset['w.reg_user_id'] = $this->session->userdata( 'ss_user_id' );
		}

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page','type'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( u.name LIKE '%{$v}%' OR w.title LIKE '%{$v}%' )"]=NULL;
				break;
				case 'date_s':
					$where['w.date_insert >=']="{$v}";
				break;
				case 'date_e':
					$where['w.date_insert <=']="{$v}";
				break;
				case 'status':
				case 'biz_id':
					$where["w.{$k}"]="{$v}";
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		// pre($where);

		$team_list = $this->User_model->get_team_list();
		$dept_list = $this->User_model->get_dept_list();
		$rs = $this->Work_model->select_bizlog_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['reg_date'] = date('Y-m-d', strtotime($row['reg_date']));
				$row['team_name'] = $team_list[$row['team_code']];
				$row['dept_name'] = $dept_list[$row['dept_code']];
				$row['status_txt'] = $this->work_status[$row['status']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		// pre($list);

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
			'paging'=>$paging
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	private function _set_work_menu($work_type) {
		switch ($work_type) {
			case 'D':
				$menu_idx = 4;
				break;
			case 'W':
				$menu_idx = 5;
				break;
			default:
				$menu_idx=0;
				break;
		}
		return $menu_idx;
	}

	public function biz_log_lists() {
		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];

		$first = ($page - 1) * $limit;
		$work_type = $this->segs[3];
		$team_list = $this->User_model->get_team_list();
		$dept_list = $this->User_model->get_dept_list();

		$where = null;
		if ($this->session->userdata( 'ss_duty_code' ) > 7) {
		} else if ($this->session->userdata( 'ss_duty_code' ) == 7) {
			$where['team_code'] = $this->session->userdata( 'ss_team_code' );
		} else {
			$where['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
		}

		if (! empty( $_GET['srch_name'] )) $where['name like '] = "%" . $_GET['srch_name'] . "%";
		if ($_GET['srch_status'] != '') $where['work_biz.status'] = $_GET['srch_status'];
		if (! empty( $_GET['srch_start_date'] )) $where["REPLACE(work_date, '-', '') >="] = str_replace( '-', '', $_GET['srch_start_date'] );
		if (! empty( $_GET['srch_end_date'] )) $where["REPLACE(work_date, '-', '') <="] = str_replace( '-', '', $_GET['srch_end_date'] );
		if (! empty( $_GET['srch_biz_id'] )) $where['work_biz.biz_id'] = $_GET['srch_biz_id'];
		if (! empty( $_GET['srch_dept_code'] )) $where['dept_code'] = $_GET['srch_dept_code'];
		if (! empty( $_GET['srch_team_code'] )) $where['team_code'] = $_GET['srch_team_code'];
		if (! empty( $_GET['srch_duty'] )) $where['duty_code'] = $_GET['srch_duty'];

		if($work_type == 'ceo') {
			$where['owner_command !='] = '';
		}
		else {
			$where['type'] = $work_type;
		}

		$result = $this->Work_model->get_biz_list( $where, $first, $limit );
		$total = $this->Work_model->get_total();

		foreach ( $result as $i => $row ) {
			$no = $total - $first - $i;
			$list->rows[$i]['id'] = $row['biz_seqno'];
			$comment_count = $this->Work_model->count_comment(array('biz_seqno'=>$row['biz_seqno']));
			$title = $row['title'];
			if($comment_count > 0) $title = $title." <span class='badge red'>".$comment_count."</span>";
			$list->rows[$i]['cell'] = array (
					'no'=>$no,
					'type'=>$this->work_type[$row['type']],
					'title'=>$title,
					'work_date'=>$row['work_date'],
					'dept_code'=>$dept_list[$row['dept_code']],
					'team_code'=>$team_list[$row['team_code']],
					'name'=>$row['name'],
					'status'=>$this->work_status[$row['status']],
					'biz_seqno'=>$row['biz_seqno'],
					'owner_command'=>nl2br($row['owner_command'])
			);
		}

		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;

		echo json_encode( $list );
	}



	private function _biz_log_input() {
		$work_type = $this->segs[3];
		$this->page_title = $this->work_type[$work_type];

		$biz_seqno = $this->segs[4];
		$sub_title = $this->work_type[$work_type];
		$row['work_date'] = date( 'Y-m-d' );
		if (! empty( $biz_seqno )) {

			$mode = 'MODIFY';
			$url_list = $this->_get_biz_url( $biz_seqno );

			$this->Work_model->table = 'work_biz';
			$row = $this->Work_model->get_info( 'biz_seqno', $biz_seqno );

			$this->_check_work_auth( $mode, $row );
		}

		$title_type = $this->_get_work_type_title( $work_type );

		$this->data['row'] = $row;
		$this->data['mode'] = $mode;
		$this->data['url_list'] = $url_list;
		$this->data['comment_list'] = $comment_list;
		$this->data['sub_title'] = $sub_title;
		$this->data['work_type'] = $this->segs[3];
		$this->data['menu_idx'] = $this->_set_work_menu( $work_type );
		$this->data['title_type'] = $title_type;
		$this->data['aSettleType'] = $this->aSettleType;

		$view = 'work/biz/input';
		return $view;
	}



	private function _get_work_type_title($work_type) {
		if ($work_type == 'W') {
			$title_type['cur-title'] = '*금주업무';
			$title_type['next-title'] = '*차주계획';
		} else {
			$title_type['cur-title'] = '*금일업무';
			$title_type['next-title'] = '*명일계획';
		}

		return $title_type;
	}

	private function _biz_log_view() {
		$work_type = $this->segs[3];
		$this->page_title = $this->work_type[strtoupper($work_type)];

		$biz_seqno = $this->segs[4];

		$this->Work_model->table = 'work_biz, user_info';
		$where = null;
		$where[] = 'work_biz.reg_user_id=user_info.user_id';
		$row = $this->Work_model->get_info( 'biz_seqno', $biz_seqno, $where, 'work_biz.*, user_info.name, user_info.dept_code, user_info.team_code' );

		$this->_check_work_auth( 'VIEW', $row );

		$row['type'] = $this->work_type[$row['type']];
		$row['status'] = $this->work_status[$row['status']];

		$url_list = $this->_get_biz_url( $biz_seqno );
		$comment_list = $this->_get_biz_comment( $biz_seqno );

		$title_type = $this->_get_work_type_title( $work_type );

		$this->data['row'] = $row;
		$this->data['url_list'] = $url_list;
		$this->data['comment_list'] = $comment_list;
		$this->data['title_type'] = $title_type;
		$this->data['work_type'] = $this->segs[3];
		$this->data['menu_idx'] = $this->_set_work_menu( $work_type );

		$view = 'work/biz/view';
		return $view;
	}

	private function _get_biz_url($biz_seqno) {
		$this->Work_model->table = 'work_biz_url';
		$result = $this->Work_model->get_common_list( 'biz_seqno', $biz_seqno, 'seqno', 'ASC' );
		foreach ( $result as $i => $url_row ) {
			$list[$i]->seqno = $url_row['seqno'];
			$list[$i]->url_type = $this->work_url_type[$url_row['url_type']];
			$list[$i]->url = $url_row['url'];
			$list[$i]->file_name = $url_row['file_name'];
			$list[$i]->new_name = $url_row['new_name'];
			$list[$i]->url_type_selected[$url_row['url_type']] = 'selected';
		}
		return $list;
	}

	private function _get_biz_comment($biz_seqno) {
		$this->Work_model->table = 'work_biz_comment';
		$result = $this->Work_model->get_common_list( 'biz_seqno', $biz_seqno, 'seqno', 'ASC' );
		foreach ( $result as $i => $comment_row ) {
			$list[$i]->seqno = $comment_row['seqno'];
			$list[$i]->comment = $comment_row['comment'];
			$list[$i]->reg_date = set_long_date_format( '-', $comment_row['reg_date'] );
			$list[$i]->name = $user_list[$comment_row['reg_user_id']];
		}

		return $list;
	}



	private function _check_work_auth($mode, $row) {
		$is_valid = true;

		if ($mode == 'MODIFY') {
			if ($row['reg_user_id'] != $this->session->userdata( 'ss_user_id' )) $is_valid = false;
		} 
		else if ($mode == 'VIEW') {
			if ($this->session->userdata( 'ss_duty_code' ) == 7 && $this->session->userdata( 'ss_team_code' ) != $row['team_code']) $is_valid = false;
			else if ($this->session->userdata( 'ss_duty_code' ) < 7 && $this->session->userdata( 'ss_duty_code' )!=1  && $this->session->userdata( 'ss_user_id' ) != $row['reg_user_id']) $is_valid = false;
		}

		if ($is_valid !== true) {
			alert( '권한이 없습니다.' );
		}
	}

	private function _paper() {
		$this->left = 'left4';
		$this->page_title = '서류관리';

		$team_list = $this->User_model->get_team_list( '20' );

		//총무팀일때
		if ( $this->session->userdata['ss_team_code'] == 22 ) :
			$this->data['paper_type'] = $this->paper_type2 ;
		else :
			$this->data['paper_type'] = $this->paper_type1;
		endif;

		$this->data['team_list'] = $team_list;
		$view = 'work/paper/index';
		return $view;
	}

	private function paper_input() {
		$seqno = $this->segs[3];
		$team_list = $this->User_model->get_team_list();
		$row['team_name'] = $this->session->userdata( 'ss_team_name' );
		$row['reg_date'] = date( 'Y-m-d H:i:s' );
		$row['name'] = $this->session->userdata( 'ss_name' );
		$mode = 'ADD';
		$valid_modify = ($this->common_lib->check_auth_group('report_delete')) ? TRUE : FALSE;
		if (! empty( $seqno )) {

			$mode = 'MODIFY';

			$this->Work_model->table = 'work_paper, user_info';
			$where[] = 'work_paper.reg_user_id=user_info.user_id';
			$row = $this->Work_model->get_info( 'seqno', $seqno, $where, 'work_paper.*, name, team_code' );
			$row['team_name'] = $team_list[$row['team_code']];
			$row['reg_date'] = set_long_date_format( '-', $row['reg_date'] );

			$result = $this->Work_model->get_common_file( 'work_paper', $seqno );

			$ext_download = array('gif','png','jpg','jpeg','bmp');

			foreach ( $result as $i => $file_row ) {
				$file_list[$i]['seqno'] = $file_row['seqno'];
				$file_list[$i]['file_name'] = $file_row['file_name'];
				$file_list[$i]['new_name'] = $file_row['new_name'];

				$ext = strtolower(array_pop(explode('.',$file_row['file_name'])));
				$is_img = (in_array($ext,$ext_download))?true:false;

				$file_list[$i]['is_img'] = $is_img;
			}

		}

		//총무팀일때
		if ( $this->session->userdata['ss_team_code'] == 22 ) :
			$this->data['paper_type'] = $this->paper_type2 ;
		else :
			$this->data['paper_type'] = $this->paper_type1;
		endif;

		$this->data['mode'] = $mode;
		$this->data['row'] = $row;
		$this->data['file_list'] = $file_list;
		$this->data['valid_modify'] = $valid_modify;

		$datum = $this->data;

		$this->_render('paper/input', $datum, 'inc');


		// $view = 'work/paper/input';
		// return $view;
	}



	private function _complain() {
		$this->page_title = '컴플레인 일지';
		$this->data['complain_type'] = $this->complain_type;
		// $this->data['doctor_list'] = $this->doctor_list[$this->session->userdata( 'ss_hst_code' )][$this->session->userdata( 'ss_biz_id' )];
		$this->data['doctor_list'] = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'));//의사
		$this->data['team_list'] = $this->User_model->get_team_list( '90' );
		$this->data['user_list'] = $this->User_model->get_team_user( '', '90' );

		$view = 'work/complain/index';
		return $view;
	}



	public function complain_lists() {
		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];

		$first = ($page - 1) * $limit;

		$user_list = $this->User_model->get_team_user( '', '90' );
		$team_list =  $this->User_model->get_team_list('90');
		$where = null;
		if (! empty( $_GET['srch_name'] )) $where['name like '] = "%" . $_GET['srch_name'] . "%";
		if (! empty( $_GET['srch_start_date'] )) $where["REPLACE(complain_date, '-', '') >="] = str_replace( '-', '', $_GET['srch_start_date'] );
		if (! empty( $_GET['srch_end_date'] )) $where["REPLACE(complain_date, '-', '') <="] = str_replace( '-', '', $_GET['srch_end_date'] );

		//등록일 검색 추가 by 이혜진 2015-05-20
		if (! empty( $_GET['srch_reg_date_s'] )) $where["work_complain.reg_date >="] = str_replace( '-', '', $_GET['srch_reg_date_s'] ).'000000';
		if (! empty( $_GET['srch_reg_date_e'] )) $where["work_complain.reg_date <="] = str_replace( '-', '', $_GET['srch_reg_date_e'] ).'235959';

		$auth = $this->common_lib->check_auth_group('complain_admin');
		if(!$auth) {
			$where['complain_charger_team'] = $this->session->userdata( 'ss_team_code' );

		}

		// if ($this->session->userdata( 'ss_dept_code' ) == '90') $where['team_code'] = $this->session->userdata( 'ss_team_code' );
		if  (! empty( $_GET['srch_team_code'] )) $where['team_code'] = $_GET['srch_team_code'];
		if  ($_GET['srch_complain_type'] != '')  $where['complain_type'] = $_GET['srch_complain_type'];
		if  (! empty( $_GET['srch_doctor'] )) $where['doctor'] = $_GET['srch_doctor'];
		if  (! empty( $_GET['srch_complain'] )) {
			$where[] = '(complain like "%'.$_GET['srch_complain'].'%" OR measure like "%'.$_GET['srch_complain'].'%")';
			// $where['complain like '] = "%" . $_GET['srch_complain'] . "%";
		}
		if  (! empty( $_GET['srch_complain_charger_id'] )) $where['complain_charger_id'] = $_GET['srch_complain_charger_id'];

		// pre($where);
		$result = $this->Work_model->get_complain_list( $where, $first, $limit );
		$total = $this->Work_model->get_total();

		foreach ( $result as $i => $row ) {
			$no = $total - $first - $i;
			$list->rows[$i]['id'] = $row['seqno'];
			$list->rows[$i]['cell'] = array (
					$no,
					set_long_date_format( '-', $row['reg_date'] ),
					$row['complain_date'],
					$this->complain_type[$row['complain_type']],
					$row['name'],
					$team_list[$row['complain_charger_team']],
					$user_list[$row['complain_charger_id']],
					$row['doctor'],
					preg_replace( '/\r\n|\r|\n/', '', $row['complain'] ),
					preg_replace( '/\r\n|\r|\n/', '', $row['measure'] ),
					tel_check( $row['tel'], '-' )
			);
		}

		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		if ($this->mode == 'EXCEL') return $list;
		else echo json_encode( $list );
	}



	private function _complain_input() {
		// $seqno = $this->segs[3];
		$seqno = $this->input->post('seqno');

		$this->data['doctor_list'] = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'));//의사
		$this->data['user_list'] = $this->User_model->get_team_user( '', '90' );
		$this->data['team_list']  = $this->User_model->get_team_list('90');

		$sub_title = '신규등록';
		$valid_modify = TRUE;
		if (! empty( $seqno )) {

			$mode = 'MODIFY';
			$sub_title = '조회/수정';

			$this->Work_model->table = 'work_complain, consulting_info';
			$where[] = 'work_complain.cst_seqno=consulting_info.cst_seqno';
			$row = $this->Work_model->get_info( 'seqno', $seqno, $where, 'work_complain.*, consulting_info.name, consulting_info.tel' );

			$row['tel'] = ($this->session->userdata( 'ss_dept_code' ) != '90') ? set_blind( 'phone', tel_check( $row['tel'], '-' ) ) : tel_check( $row['tel'], '-' );
			$row['name_tel'] = $row['name'] . ' / ' . $row['tel'];

			$valid_modify = ($row['reg_user_id'] == $this->session->userdata( 'ss_user_id' )) ? TRUE : FALSE;
		}

		$this->data['valid_modify'] = $valid_modify;
		$this->data['mode'] = $mode;
		$this->data['row'] = $row;
		$this->data['sub_title'] = $sub_title;
		$this->data['complain_type'] = $this->complain_type;
		$view = 'work/complain/input';
		return $view;
	}



	public function excel_lists() {
		$this->yield = FALSE;

		$type = $this->segs[3];
		$this->mode = 'EXCEL';
		$list = $this->{$type . '_lists'}();

		$this->_set_page_title( $type );
		output_excel( iconv( 'utf-8', 'euckr', str_replace( ' ', '', $this->page_title ) . '_' . date( 'Ymd' ) ) );
		$this->load->view( 'work/' . $type . '/excel', array (
				'list'=>$list
		) );
	}



	private function _set_page_title($type) {
		$title_list = array (
				'complain'=>'컴플레인 관리 일지'
		);

		$this->page_title = $title_list[$type];
	}



	public function paper_lists() {
		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];

		$first = ($page - 1) * $limit;

		$team_list = $this->User_model->get_team_list();

		$where = null;

		switch ($this->session->userdata( 'ss_duty_code' )) {
			case '7': //팀장
				$where['team_code'] = $this->session->userdata( 'ss_team_code' );
			break;
			case '8': //총괄
				$where['dept_code'] = $this->session->userdata( 'ss_dept_code' );
			break;
			case '9': //대표이사
			break;
			default :
				$where['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
			break;
		}


		// if ($this->session->userdata( 'ss_duty_code' ) > 8) {
		// }

		// else if ($this->session->userdata( 'ss_duty_code' ) == 7) {
		// 	$where['team_code'] = $this->session->userdata( 'ss_team_code' );
		// }
		// else {
		// 	$where['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
		// }

		if (! empty( $_GET['srch_team_code'] )) $where['team_code'] = $_GET['srch_team_code'];

		if ($_GET['srch_paper_type'] != '') $where['paper_type'] = $_GET['srch_paper_type'];
		if (! empty( $_GET['srch_start_date'] )) $where['work_paper.reg_date >='] = str_replace( '-', '', $_GET['srch_start_date'] ) . '000000';
		if (! empty( $_GET['srch_end_date'] )) $where['work_paper.reg_date <='] = str_replace( '-', '', $_GET['srch_end_date'] ) . '999999';
		if (! empty( $_GET['srch_name'] )) $where['name like '] = "%" . $_GET['srch_name'] . "%";
		if (! empty( $_GET['srch_title'] )) $where['title like '] = "%" . $_GET['srch_title'] . "%";

		$result = $this->Work_model->get_paper_list( $where, $first, $limit );
		$total = $this->Work_model->get_total();

		foreach ( $result as $i => $row ) {
			$no = $total - $first - $i;
			$list->rows[$i]['id'] = $row['seqno'];
			$list->rows[$i]['cell'] = array (
					$no,
					set_long_date_format( '-', $row['reg_date'] ),
					$team_list[$row['team_code']],
					$row['name'],
					$this->paper_type[$row['paper_type']],
					$row['title']
			);
		}

		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		echo json_encode( $list );
	}



	private function _view_common_file() {
		$this->layout = 'blank_layout';

		$seqno = $this->segs[3];
		$file_seqno = $this->segs[4];

		$result = $this->Work_model->get_common_file( 'work_paper', $seqno );
		foreach ( $result as $i => $file_row ) {
			$file_list[$i]->seqno = $file_row['seqno'];
			$file_list[$i]->file_name = $file_row['file_name'];
			$file_list[$i]->new_name = $file_row['new_name'];
		}

		$this->data['file_list'] = $file_list;
		$view = 'work/paper/file';
		return $view;
	}

	function download() {
		$this->load->helper('download');

		$seqno = $this->segs[3];
		$file_seqno = $this->segs[4];
		$result = $this->Work_model->get_common_file( 'work_paper', $seqno, $file_seqno );
		$ori_name = $result[0]['file_name'];
		$real_path = "./DATA/work/paper/".$result[0]['new_name'];
	    $data = file_get_contents($real_path);

	    // 한글 파일명 IE에서 깨짐 처리
	    force_download(mb_convert_encoding($ori_name, 'euc-kr', 'utf-8'), $data);
	}

	function _work_ser () {

		switch ( $_POST['ptempindex']) {
			case "22" :
				$data['paper_type'] = $this->paper_type2 ;
			break ;
			default :
				$data['paper_type'] = $this->paper_type1;
			break;
		}

		$this->load->view("work/paper/papers_serch" , $data);
	}

	function _happycall() {
		//상담팀인경우 본인소속팀만 선택가능함
		if($this->session->userdata('ss_dept_code')=='90') {
			$team = array($this->session->userdata('ss_team_code')=>$this->session->userdata('ss_team_name'));
		}
		else {
			$team = $this->common_lib->get_team( '90' );
		}
		$treat_region = $this->common_lib->get_code_item( '01' ); //진료부위
		$doctor = $this->common_lib->get_cfg('doctor');//의사
		$srch_date = get_search_type_date(); //검색날짜

		$datum = array(
			'cfg'=>array(
				'team'=>$team,
				'treat_region'=>$treat_region,
				'doctor'=>$doctor,
				'date'=>$srch_date
			)
		);
		$this->_render('happycall/index', $datum);
		// $view = 'work/happycall/index';
		// return $view;
	}

	function _happycall_input() {
		$p = $this->input->post(NULL, true);
		$this->load->library('patient_lib');
		$happycall_no = $p['happycall_no'];

		//상담팀인경우 본인소속팀만 선택가능함
		if($this->session->userdata('ss_dept_code')=='90') {
			$team = array($this->session->userdata('ss_team_code')=>$this->session->userdata('ss_team_name'));
		}
		else {
			$team = $this->common_lib->get_team( '90' );
		}


		//$treat_region = $this->common_lib->get_code_item( '01' ); //진료부위
		$doctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'));//의사

		if($happycall_no>0) {
			$mode = 'update';
			$row = $this->Work_model->select_happycall_row(array('no'=>$happycall_no));
			$row['grant'] = ($row['writer_id']==$this->session->userdata('ss_user_id'))?true:false;
			list($customer_name, $customer_tel) = explode('/',$row['customer']);
			$row['custome_name'] = $customer_name.'/'.set_blind('phone',$customer_tel);
			$row['treat_nav'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
			$happycall_info = $row;
		}
		else {
			$mode = 'insert';
			if($p['cst_seqno'] > 0) {
				$this->load->model('Consulting_Model');
				$cst_info = $this->Consulting_Model->select_consulting_row(array('cst_seqno'=>$p['cst_seqno']));

				$happycall_info = array(
					'custome_name'=>$cst_info['name'].'/'.set_blind('phone',$cst_info['tel']),
					'cst_seqno'=>$p['cst_seqno'],
					'grant'=>true,
					'operation_date'=>date('Y-m-d')
				);
			}
			else {
				$happycall_info = array(
					'grant'=>true,
					'operation_date'=>date('Y-m-d')
				);
			}

		}



		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'team'=>$team,
				'doctor'=>$doctor
			),
			'rs'=>$happycall_info
		);

		// $this->data = $datum;
		$this->_render('happycall/input', $datum, 'inc');
		// $view = 'work/happycall/input';
		// return $view;
	}

	function _happycall_list_paging() {
		$this->load->library('patient_lib');
		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where_offset = array(
			'c.biz_id'=>$this->session->userdata('ss_biz_id')
		);
		$where =  array();

		//상담팀인경우 본인 팀담당글만 보임
		$dept = $this->session->userdata('ss_dept_code');
		if($dept == '90') {
			$where_offset['manager_team_code'] = $this->session->userdata('ss_team_code');
		}

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'search_word':
					$where["(customer LIKE '%{$v}%' OR comment LIKE '%{$v}%')"]=NULL;
				break;
				case 'date_insert_start':
					$where['date_insert >=']="{$v} 00:00:00";
				break;
				case 'date_insert_end':
					$where['date_insert <=']="{$v} 23:59:59";
				break;
				case 'operation_date_start':
					$where['operation_date >=']="{$v} 00:00:00";
				break;
				case 'operation_date_end':
					$where['operation_date <=']="{$v} 23:59:59";
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$where = array_merge($where, $where_offset);

		// pre($where);

		$users = $this->common_lib->search_user(array('status'=>1));
		$team = $this->common_lib->get_team( '90' );
		$treat_region = $this->common_lib->get_code_item( '01' ); //진료부위
		$treat_item = $this->common_lib->get_code_item( '02' ); //진료항목


		$rs = $this->Work_model->select_happycall_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['date_insert'] = substr($row['date_insert'],0,10);
				$row['writer_name'] = $users[$row['writer_id']]['name'];
				$row['doctor_name'] = $users[$row['doctor_id']]['name'];
				$row['manager_team_name'] = $team[$row['manager_team_code']];
				$row['manager_name'] = $users[$row['manager_id']]['name'];

				$row['treat_info'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
				list($customer_name, $customer_tel) = explode('/',$row['customer']);
				$row['custome_name'] = $customer_name.'/'.set_blind('phone',$customer_tel);

				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
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
			'list'=>$list,
			'paging'=>$paging
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	function _minutes() {
		$srch_date = get_search_type_date(); //검색날짜

		$datum = array(
			'cfg'=>array(
				'date'=>$srch_date
			)
		);
		$this->data = $datum;
		$view = 'work/minutes/index';
		return $view;
	}

	function _minutes_list_paging() {
		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'search_word':
					$where['issues LIKE']="%{$v}%";
				break;
				case 'date_s':
					$where['minutes_start >=']="{$v} 00:00:00";
				break;
				case 'date_e':
					$where['minutes_end <=']="{$v} 23:59:59";
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		// pre($where);

		$users = $this->common_lib->search_user(array('status'=>1));

		$rs = $this->Work_model->select_minutes_paging($where, $offset, $limit);
		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['minutes_date'] = substr($row['minutes_start'],0,10);
				$row['writer_name'] = $users[$row['writer_id']]['name'];
				$attendees = explode(',',$row['attendees']);
				$attendee_first = array_shift($attendees);
				$row['attendees'] = $users[$attendee_first]['name'].'외 '.count($attendees).'명';
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}

			// pre($rs);

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
			'list'=>$list,
			'paging'=>$paging
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	function _minutes_input() {
		// pre($this->session->userdata);
		$p = $this->input->post(NULL, true);
		$minutes_no = $p['minutes_no'];
		$room = $this->common_lib->get_code_item('08');

		if($minutes_no>0) {
			$mode = 'update';
			$users = $this->common_lib->search_user(array('status'=>1));
			$team = $this->common_lib->get_team(); //팀

			$row = $this->Work_model->select_minutes_row(array('no'=>$minutes_no));
			$row['writer_name'] = $users[$row['writer_id']]['name'];
			$row['minutes_date'] = substr($row['minutes_start'],0,10);
			$row['time_start'] = substr($row['minutes_start'],11,5);
			$row['time_end'] = substr($row['minutes_end'],11,5);

			$attendees = explode(',',$row['attendees']);
			foreach($attendees as $user_id) {
				$user = $users[$user_id];
				$attendees_list[$user_id] = array(
					'label'=>$team[$user['team_code']].' > '.$user['name'],
					'name'=>$user['name']
				);
			}
			$row['attendees_list'] = $attendees_list;

			$opinion_attendees = unserialize($row['opinion_attendees']);
			foreach($opinion_attendees as $user_id=>$opinion) {
				$opinion_list[$user_id] = array(
					'name'=>$users[$user_id]['name'],
					'opinion'=>$opinion
				);
			}
			$row['opinion_list'] = $opinion_list;


			$minutes_info = $row;
			// pre($row);
		}
		else {
			$mode = 'insert';
			$minutes_info = array(
				'minute_date'=>date('Y-m-d'),
				'writer_id'=>$this->session->userdata('ss_user_id'),
				'writer_name'=>$this->session->userdata('ss_name')
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'room'=>$room
			),
			'rs'=>$minutes_info
		);
		// pre($datum);
		$this->data = $datum;
		$view = 'work/minutes/input';
		return $view;
	}

	/**
	 * 회의록 보기
	 * @return [type] [description]
	 */
	function _minutes_view() {
		$p = $this->input->post(NULL, true);
		$minutes_no = $p['minutes_no'];
		$users = $this->common_lib->search_user(array('status'=>1));
		$position = $this->config->item('position_code');


		$room = $this->common_lib->get_code_item('08');

		$row = $this->Work_model->select_minutes_row(array('no'=>$minutes_no));

		$row['minutes_datetime'] = $row['minutes_start'].'~'.substr($row['minutes_start'],10,5);
		$row['writer_name'] = $users[$row['writer_id']]['name'];
		if($row['room_code'] == 'etc') $row['room'] = '기타 ('.$row['room_etc'].')';
		else $row['room'] = $room[$row['room_code']];

		//참석자
		$attendees = explode(',',$row['attendees']);
		foreach($attendees as $attendee) {
			$user = $users[$attendee];
			$attendees_list[] = $user['name'].' '.$position[$user['position_code']];
		}
		$row['attendees_list'] = implode(', ',$attendees_list);

		//참석자 의견
		$opinion_attendees = unserialize($row['opinion_attendees']);
		foreach($opinion_attendees as $user_id=>$opinion) {
			$opinion_list[] = array(
				'name'=>$users[$user_id]['name'],
				'opinion'=>$opinion
			);
		}
		$row['opinion_list'] = $opinion_list;
		$row['grant'] = ($row['writer_id']==$this->session->userdata('ss_user_id'))?true:false;

		$datum = array(
			'rs'=>$row
		);
		// pre($datum);
		$this->data = $datum;
		$view = 'work/minutes/view';
		return $view;
	}

	function _report() {
		$view = 'work/report/index';
		return $view;
	}

	function comment_lists() {
		$where = array(
			'paper_no'=>$this->input->post('paper_no')
		);
		$rs = $this->Work_model->select_comment($where);
		$list = array();
		foreach ($rs as $row) {
			$row['writer_info'] = unserialize($row['writer_info']);
			$list[] = $row;
		}
		$datum = array(
			'comment'=>$list
		);
		// pre($list);
		$this->_render('paper/paper_comment', $datum, 'inc');

	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "/work/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}




	// 20170307 kruddo : 업무일지별 포맷
	/**
	 * 기안별 기본 포맷 가져오기
	 * @return [type] [description]
	 */
	public function _settle_format() {
		$biz_type = $this->input->post( 'biz_type' );
		$biz_seqno = $this->input->post( 'biz_seqno' );

		if($biz_seqno>0) {
			//$settle_info = $this->Business_model->get_settle(array('no'=>$biz_seqno), 'reg_date', '' ,'row');
			$work_biz_expense = $this->Work_model->get_biz_expense(array('s.biz_seqno'=>$biz_seqno));
		}
		else {
			$work_biz_expense = array(
				array(
					'biz_seqno'=>''
				)
			);
		}

		$datas = array(

			'settle_no'=>$settle_no,
			'work_biz_expense_doctor' => $this->aSettleExpenseDoctor,
			'path_list'=>$this->config->item( 'all_path' ),
			'work_biz_expense'=>$work_biz_expense
		);



		$this->load->view('work/format/'.$biz_type, $datas);
	}
	// 20170307 kruddo : 업무일지별 포맷


	// 20170308 kruddo : 일일매출보고(공통)
	public function biz_sales_log() {
		$work_type = $this->segs[3];

		$team = $this->User_model->get_team_list(90);
		$team2 = $this->User_model->get_team_list(50);
		$search_team = 90;
		switch($work_type) {
			case 'C':

			break;
			case 'D':
				$team = array();
				foreach($team2 as $k=>$v) {
					if($k == 51)		$team[$k] = $v;
				}
				$search_team = 51;
			break;
			case 'S':
				$team = array();
				foreach($team2 as $k=>$v) {
					if($k == 50)		$team[$k] = $v;
				}
				$search_team = 50;
			break;
			default:
				foreach($team2 as $k=>$v) {
					if($k == 50 || $k == 51)	$team[$k] = $v;
				}
			break;
		}

		/*
		$team = $this->User_model->get_team_list(90);
		$team2 = $this->User_model->get_team_list(50);
		foreach($team2 as $k=>$v) {
			if($k == 50 || $k == 51){
				$team[$k] = $v;
			}
		}
		*/

		$datum = array(
			'cfg'=>array(
				'date'=>$this->common_lib->get_cfg(array('date')),
				'team'=>$team,
				'work_type'=>$work_type,
			),
			'search'=>array(
				'team_code_search'=>$search_team
			),
		);

		$this->_render('biz/biz_sales_log', $datum );
		//return_json(true, '', $datum);
	}

	/**
	 * 팀별 기본 포맷 가져오기
	 * @return [type] [description]
	 */
	 /*
	public function biz_format() {
		$biz_type = $this->input->post( 'biz_type' );

		$datas = array(
			'work_biz_expense_doctor' => $this->aSettleExpenseDoctor,
			'path_list'=>$this->config->item( 'all_path' ),
			'date'=>$this->common_lib->get_cfg(array('date')),
			'expense_consultant_list' => $this->common_lib->get_cfg('consultant'),	//상담사
		);


		$this->load->view('work/format/'.$biz_type, $datas);
		if($biz_type == 1){
			$this->load->view('work/format/'.$biz_type.'_1', $datas);
		}
	}
	*/

	// 20170308 kruddo : 일일매출보고(상담팀)
	public function biz_sales_list_paging($assoc=''){
		/*

		$p = $this->param;

		$this->load->library('patient_lib');
		$this->load->model('patient_model');

		if(empty($assoc)) {
			parse_str($this->input->post('search'), $assoc);
		}

		$_SESSION['search'] = array_filter($assoc); //검색데이터 세션처리
		*/

		$where = array();
		$where['is_delete'] = 'N';

		$date_s = $this->input->post('date_s');
		$date_e = $this->input->post('date_e');
		$team_code = $this->input->post('team_code_search');
		$biz_type = $this->input->post( 'biz_type' );
		$is_schedule = $this->input->post( 'is_schedule' );
		$work_type = $this->input->post( 'work_type' );

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('type','limit', 'page', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'date_s':
					$date_s=$v;
				break;
				case 'date_e':
					$date_e=$v;
				break;
				case 'team_code_search':
					$team_code=$v;
				break;
				case 'biz_type':
					$biz_type=$v;
				break;
				case 'is_schedule':
					$is_schedule=$v;
				break;
				case 'work_type':
					$work_type=$v;
				break;

				default :

				break;
			}
		}

		$where['team_code'] = $team_code;


		switch($biz_type) {
			case '2':
				$where['expense_consulting_date >='] = $date_s;
				$where['expense_consulting_date <='] = $date_e;


				if($is_schedule == 'Y'){
					$datum = $this->biz_sales_list_codi_op($where);
				}
				else{
					$datum = $this->biz_sales_list_codi($where);
				}
			break;
			case '3':
				$where['expense_op_date >='] = $date_s;
				$where['expense_op_date <='] = $date_e;

				$datum = $this->biz_sales_list_skin($where);
			break;

			default :
				$where['is_schedule'] = $is_schedule;
				$where['expense_project_date >='] = $date_s;
				$where['expense_project_date <='] = $date_e;

				$datum = $this->biz_sales_list($where);
			break;
		}

		$team = $this->User_model->get_team_list(90);
		$team2 = $this->User_model->get_team_list(50);
		foreach($team2 as $k=>$v) {
			if($k == 50 || $k == 51){
				$team[$k] = $v;
			}
		}

		$datum['cfg'] = array(
				'date'=>$this->common_lib->get_cfg(array('date')),
				'team'=>$team,
				'work_biz_expense_doctor' => $this->aSettleExpenseDoctor,
				'path_list'=>$this->config->item( 'all_path' ),
				'expense_consultant_list' => $this->common_lib->get_cfg('consultant'),	//상담사
				'work_type' => $work_type,
		);
//		return_json(true, '', $datum);


		if($this->output=='excel') {
			$datum['search_date'] = $date_s.' ~ '.$date_e;
			return $datum;
		}
		else{
			if($is_schedule == 'Y'){
				$biz = $biz_type.'_1';
			}
			else{
				$biz = $biz_type;
			}

			$datum['search_date'] = $date_s.' ~ '.$date_e;
			$this->_render('format/'.$biz, $datum, 'inc');
		}

		//return_json(true, '', $datum);
	}

	function biz_sales_list_db($assoc=''){
		/*
		$p = $this->param;

		$this->load->library('patient_lib');
		$this->load->model('patient_model');

		if(empty($assoc)) {
			parse_str($this->input->post('search'), $assoc);
		}

		$_SESSION['search'] = array_filter($assoc); //검색데이터 세션처리
		*/

		$where = array();
		$where_offset = array();
		$orderby = '';

		$date_s = $this->input->post('date_s');
		$date_e = $this->input->post('date_e');
		$date_db = $this->input->post('date_db');
		$team_code = $this->input->post('team_code_search');

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('type','limit', 'page', 'grant_biz'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'date_s':
					$date_s=$v;
				break;
				case 'date_e':
					$date_e=$v;
				break;
				case 'date_db':
					$date_db=$v;
				break;
				case 'team_code_search':
					$team_code=$v;
				break;
				default :
				break;
			}
		}

		//$date_db = '2017-02-03';
		//$team_code = 90;

		$where['team_code'] = $team_code;
		$where['date_format(c.reg_date, "%Y-%m-%d")>='] = substr($date_db, 0, 7)."-01";
		$where['date_format(c.reg_date, "%Y-%m-%d")<='] = $date_db;	//substr($date_db, 0, 7);


		$rs = $this->Work_model->select_biz_expense_db($where,$offset, $limit, $where_offset, $orderby);

		$list = array();
		$date_list = array();
		foreach($rs['list'] as $row) {

			$list["path_".$row['path']] += $row['cnt'];
			if($row['path'] == 'L' || $row['path'] == 'T' || $row['path'] == 'W' || $row['path'] == '1' || $row['path'] == '6' || $row['path'] == '5' || $row['path'] == 'P' || $row['path'] == 'O' || $row['path'] == 'K'){
				$list["path_total"] += $row['cnt'];
			}

			if($row['regdate'] == $date_db){
				$date_list["path_".$row['path']] += $row['cnt'];
				if($row['path'] == 'L' || $row['path'] == 'T' || $row['path'] == 'W' || $row['path'] == '1' || $row['path'] == '6' || $row['path'] == '5' || $row['path'] == 'P' || $row['path'] == 'O' || $row['path'] == 'K'){
					$date_list["path_total"] += $row['cnt'];
				}
			}
		}

		$datum = array(
			'list'=>$list,
			'date_list'=>$date_list
		);
		$datum['search_date'] = $date_s.' ~ '.$date_e;
		$datum['db_date'] = $date_db;
		$datum['db_date_period'] = substr($date_db, 2, 5)."-01"." ~ ".substr($date_db, 2, 8);

		$this->_render('format/1_2', $datum, 'inc');
		//return_json(true, '', $datum);

	}

	function biz_sales_list($where){

		//검색조건설정
		$this->Work_model->table = 'work_biz_expense';

		$this->load->Model('Manage_Model');
		$doctor = $this->common_lib->get_cfg('doctor');
		$consultant = $this->common_lib->get_cfg('consultant');
		$path_list = $this->config->item( 'all_path' );

		$orderby = 'expense_project_date asc, expense_consulting_date asc, no ASC';

		$where_offset=array();

		$rs = $this->Work_model->select_biz_expense_paging($where,$offset, $limit, $where_offset, $orderby);

		$list = array();
		$total = array();
		//$idx =  count($rs['list']);
		$idx = 1;

		foreach($rs['list'] as $row) {
			$row['path'] = $path_list[$row['expense_path']];
			$row['doctor_name'] = $doctor[$row['expense_doctor']];
			$row['expense_consultant_name'] = $consultant[$row['expense_consultant']]['name'];
			$row['idx'] = $idx;
			$row['expense_consulting_date'] = set_date_format('Y-m-d', $row['expense_consulting_date']);
			$row['expense_project_date'] = set_date_format('Y-m-d', $row['expense_project_date']);


			$total['expense_receipt_price'] += $row['expense_receipt_price'];
			$total['expense_receipt_money'] += $row['expense_receipt_money'];
			$total['expense_receipt_card'] += $row['expense_receipt_card'];
			$total['expense_receipt_account'] += $row['expense_receipt_account'];

			$total['expense_deposit_refund'] += $row['expense_deposit_refund'];
			$total['expense_deposit_unpaid'] += $row['expense_deposit_unpaid'];
			$total['expense_deposit_price'] += $row['expense_deposit_price'];

			$list[] = $row;
			$idx++;
			//$idx--;
		}


		//
		$datum = array(
			'list'=>$list,
			'mode'=>$this->output,
			'total'=>$total
		);

		return $datum;

	}
	// 20170308 kruddo : 일일매출보고(상담팀)



	// 20170308 kruddo : 일일매출보고(코디팀)
	function biz_sales_list_codi($where){
		$this->Work_model->table = 'work_biz_expense_codi';

		$expense_consultant_list = $this->common_lib->get_cfg('consultant');	//상담사

		$this->load->library('patient_lib');
		$this->load->model('patient_model');

		//검색조건설정

		$this->load->Model('Manage_Model');
		$doctor = $this->common_lib->get_cfg('doctor');
		$path_list = $this->config->item( 'all_path' );

		$orderby = 'expense_consulting_date asc, no ASC';

		$where_offset = array();

		$rs = $this->Work_model->select_biz_expense_paging($where,$offset, $limit, $where_offset, $orderby);

		$list = array();
		$total = array();
		$idx =  count($rs['list']);

		if($idx > 0){
			$etc_list = array();

			foreach($rs['list'] as $row) {
				$row['path'] = $path_list[$row['expense_path']];
				$row['doctor_name'] = $doctor[$row['expense_doctor']];
				$row['idx'] = $idx;
				$row['expense_consulting_date'] = set_date_format('Y-m-d', $row['expense_consulting_date']);
				$row['expense_project_date'] = set_date_format('Y-m-d', $row['expense_project_date']);

				$row['expense_consulting_name'] = $expense_consultant_list[$row['expense_consulting']]['name'];
				$row['expense_closing_name'] = $expense_consultant_list[$row['expense_closing']]['name'];


				$total['expense_receipt_price'] += $row['expense_receipt_price'];
				$total['expense_receipt_money'] += $row['expense_receipt_money'];
				$total['expense_receipt_card'] += $row['expense_receipt_card'];
				$total['expense_receipt_account'] += $row['expense_receipt_account'];
				$total['expense_deposit_price'] += $row['expense_deposit_price'];


				$etc_list[0]['name'] = '총내원수';
				$etc_list[1]['name'] = '방문예치';
				$etc_list[2]['name'] = '전화예치';
				$etc_list[3]['name'] = '당일수술';

				switch($row['expense_div']) {
					case '신환':
						$etc_list[0]['new'] = $etc_list[0]['new']+1;

						if($row['expense_deposit_stay'] == "방문")		$etc_list[1]['new'] = $etc_list[1]['new']+1;
						else											$etc_list[2]['new'] = $etc_list[2]['new']+1;

						if($row['expense_project_today_yn'] == "Y")		$etc_list[3]['new'] = $etc_list[3]['new']+1;

					break;
					case '구환':
						$etc_list[0]['old'] = $etc_list[0]['old']+1;

						if($row['expense_deposit_stay'] == "방문")		$etc_list[1]['old'] = $etc_list[1]['old']+1;
						else											$etc_list[2]['old'] = $etc_list[2]['old']+1;

						if($row['expense_project_today_yn'] == "Y")		$etc_list[3]['old'] = $etc_list[3]['old']+1;
					break;
				}



				$list[] = $row;
				$idx--;
			}

			////////////////////////////////////
			$receipt_method[0]['name'] = '총매출';
			$receipt_method[0]['price'] = $total['expense_receipt_card']+$total['expense_receipt_money']+$total['expense_receipt_account'];
			$receipt_method[0]['idx'] = 1;
			$receipt_method[1]['name'] = '카드';
			$receipt_method[1]['price'] = $total['expense_receipt_card'];
			$receipt_method[2]['name'] = '현금';
			$receipt_method[2]['price'] = $total['expense_receipt_money'];
			$receipt_method[3]['name'] = '계좌';
			$receipt_method[3]['price'] = $total['expense_receipt_account'];
			///////////////////////////////////

			$datum = array(
				'list'=>$list,
				'mode'=>$this->output,
				'total'=>$total,
				'receipt_method'=>$receipt_method,
				'etc_list'=>$etc_list,
			);
		}

		return $datum;

	}
	// 20170308 kruddo : 일일매출보고(코디팀)



	// 20170308 kruddo : 일일매출보고(코디팀 OP)
	function biz_sales_list_codi_op($where){
		$this->Work_model->table = 'work_biz_expense_codi_op';

		$expense_consultant_list = $this->common_lib->get_cfg('consultant');	//상담사

		$this->load->library('patient_lib');
		$this->load->model('patient_model');

		//검색조건설정

		$this->load->Model('Manage_Model');
		$doctor = $this->common_lib->get_cfg('doctor');
		$path_list = $this->config->item( 'all_path' );

		$orderby = 'expense_consulting_date asc, no ASC';

		$where_offset = array();

		$rs = $this->Work_model->select_biz_expense_paging($where,$offset, $limit, $where_offset, $orderby);

		$list = array();
		$total = array();
		$treatment = array();

		$idx =  count($rs['list']);

		if($idx > 0){
			$etc_list = array();

			foreach($rs['list'] as $row) {

				$row['expense_consulting_date'] = set_date_format('Y-m-d', $row['expense_consulting_date']);
				$row['idx'] = $idx;
				$row['doctor_name'] = $doctor[$row['expense_doctor']];
				$row['expense_consulting_name'] = $expense_consultant_list[$row['expense_consulting']]['name'];


				///////////////////////////////////////////////////////////////////////////////////////////
				$total['expense_op_price'] += $row['expense_op_price'];
				$total['expense_provide_price'] += $row['expense_provide_price'];
				$total['expense_surtax_price'] += $row['expense_surtax_price'];

				$total['expense_op_money'] += $row['expense_op_money'];
				$total['expense_op_card'] += $row['expense_op_card'];
				$total['expense_op_account'] += $row['expense_op_account'];

				$total['expense_deposit_money'] += $row['expense_deposit_money'];
				$total['expense_deposit_card'] += $row['expense_deposit_card'];
				$total['expense_deposit_account'] += $row['expense_deposit_account'];

				$total['expense_reserve_account'] += $row['expense_reserve_account'];
				$total['expense_unpaid_account'] += $row['expense_unpaid_account'];
				$total['expense_deposit_price'] += $row['expense_deposit_price'];
				///////////////////////////////////////////////////////////////////////////////////////////

				$treatment[$row['expense_item']]['treatment_name'] = $row['expense_item'];
				$treatment[$row['expense_item']]['treatment_cnt'] = $treatment[$row['expense_item']]['treatment_cnt']+1;


				$list[] = $row;
				$idx--;
			}


			$datum = array(
				'list'=>$list,
				'mode'=>$this->output,
				'total'=>$total,
				'treatment'=>$treatment,
				//'receipt_method'=>$receipt_method,
				//'etc_list'=>$etc_list,
			);
		}

		return $datum;

	}
	// 20170308 kruddo : 일일매출보고(코디팀 OP)





	// 20170308 kruddo : 일일매출보고(피부팀)
	function biz_sales_list_skin($where){
		$this->Work_model->table = 'work_biz_expense_skin';

		$expense_consultant_list = $this->common_lib->get_cfg('consultant');	//상담사

		$this->load->library('patient_lib');
		$this->load->model('patient_model');

		//검색조건설정

		$this->load->Model('Manage_Model');
		$doctor = $this->common_lib->get_cfg('doctor');
		$path_list = $this->config->item( 'all_path' );

		$orderby = 'expense_op_date asc, no ASC';

		$where_offset = array();

		$rs = $this->Work_model->select_biz_expense_paging($where,$offset, $limit, $where_offset, $orderby);

		$list = array();
		$total = array();
		$idx =  count($rs['list']);

		//return_json(true, '', $expense_consultant_list);

		if($idx > 0){
			foreach($rs['list'] as $row) {
				$row['path'] = $path_list[$row['expense_path']];
				$row['doctor_name'] = $doctor[$row['expense_doctor']];
				$row['idx'] = $idx;
				$row['expense_op_date'] = set_date_format('Y-m-d', $row['expense_op_date']);

				//$row['expense_consulting_name'] = $expense_consultant_list[$row['expense_consulting']]['name'];
				//$row['expense_closing_name'] = $expense_consultant_list[$row['expense_closing']]['name'];


				$total['expense_receipt_price'] += $row['expense_receipt_price'];

				$list[] = $row;
				$idx--;
			}

			//
			$datum = array(
				'list'=>$list,
				'mode'=>$this->output,
				'total'=>$total
			);
		}

		return $datum;

	}
	// 20170308 kruddo : 일일매출보고(피부팀)


	function excel() {
		$p = $this->input->get(NULL, true);
		$this->param['limit'] = $p['limit'];
		$this->output="excel";

		$this->load->dbutil();
		$this->load->helper('download');


		$biz_type = $p['biz_type'];
		$biz = $biz_type.'.html';

		$team_name = $p['team_name'];

		if($biz_type == "1"){

		}
		else if($biz_type == "2"){

		}
		else if($biz_type == "3"){

		}

		$filename = $team_name." 일일매출보고서.xls";
		$list = $this->biz_sales_list_paging($p);

		$body = $this->layout_lib->fetch_('/work/format/'.$biz, $list);


		if($biz_type == "1"){
			$p['is_schedule'] = 'Y';

			$list = $this->biz_sales_list_paging($p);

			$biz = $biz_type.'_1.html';
			$body .= $this->layout_lib->fetch_('/work/format/'.$biz, $list);
		}
		else if($biz_type == "2"){
			$p['is_schedule'] = 'Y';

			$list = $this->biz_sales_list_paging($p);

			$biz = $biz_type.'_1.html';
			$body .= $this->layout_lib->fetch_('/work/format/'.$biz, $list);
		}

		force_download($filename, $body);
	}
	// 20170308 kruddo : 일일매출보고(공통)
}
