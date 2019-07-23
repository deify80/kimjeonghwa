<?php
/**
 * 작성 : 2014.10.28
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Manage extends CI_Controller {
	var $data = null;


	public function __construct() {
		parent::__construct();
		session_start();

		$this->load->model( array (
				'Manage_model',
				'User_model',
				'Patient_Model'
		) );

		$this->load->helper('date');
	}

	public function _remap($method) {
		$this->yield = TRUE;
		$this->segs = $this->uri->segment_array();

		if (method_exists( $this, '_' . $method )) {
			$view = $this->{'_' . $method}();
			if (! empty( $view )) $this->load->view( $view, $this->data );
		} else {
			$this->$method();
		}
	}

	/**
	 * 직원현황
	 * @return [type] [description]
	 */
	private function user_main() {
		$dept_list = $this->User_model->get_dept_list( $where );
		$team_list = $this->User_model->get_team_list();
		$position_list = $this->config->item( 'position_code' );
		$duty_list = $this->config->item( 'duty_code' );


		$search = $_SESSION['search'];
		if(empty($search)) {
			$search = array(
				'dept_code'=>'all',
				'team_code'=>'all',
				'status'=>1,
				'duty_code'=>'all',
				'positoin_code'=>'all'
			);
		}
		$datum = array (
			'cfg'=>array(
				'dept'=>$dept_list,
				'duty'=>$duty_list,
				'position'=>$position_list,
				'biz'=>$this->session->userdata( 'ss_biz_list' )
			),
			'search'=>$search,
			'page'=>($search['page']>0)?$search['page']:1
		);

		// pre($search);

		$this->_render('user/index', $datum);
	}



	public function user_list_paging() {

		parse_str($this->input->post('search'), $assoc);

		$_SESSION['search'] = array_filter($assoc);//검색데이터 세션처리

		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('page','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'search_word':
					if($v) {
						$where["(name LIKE '%{$v}%' OR user_code LIKE '%{$v}%' OR user_id LIKE '%{$v}%')"]=NULL;
					}
				break;

				case 'biz_id':
					$where["CONCAT(',',biz_id,',') LIKE '%,{$v},%'"]=null;
				break;
				default :
					$where[$k] = "{$v}";
					break;
			}
		}

		// pre($where);

		$where_offset = array(); //array('status'=>'1');

		$rs = $this->User_model->select_user_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {
			$dept_list = $this->User_model->get_dept_list(); //부서
			$team_list = $this->User_model->get_team_list(); //팀
			$position_list = $this->config->item( 'position_code' );
			$duty_list = $this->config->item( 'duty_code' );

			$this->load->helper('date');
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['dept_name'] = $dept_list[$row['dept_code']];
				$row['team_name'] = $team_list[$row['team_code']];
				$row['position_name'] = $position_list[$row['position_code']];
				$row['duty_name'] = $duty_list[$row['duty_code']];
				$row['idx'] = $idx;
				if($row['join_date']) {
					$service = (date_diff($row['join_date'], date('Y-m-d')));
				}
				else {
					$serivice = array('years'=>'','months'=>'','days'=>'');
				}
				$row['service'] = $service;

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

	private function _password() {
		$datum = array();
		$this->_render('user/password', $datum, 'inc');
	}

	private function _user_info() {
		$user_no = $this->uri->segment(3);
		$user_info = $this->User_model->get_info(array('no'=>$user_no));

		$this->data = array(
			'basic'=>$this->_user_manufacture($user_info)
		);
		$view = '/manage/user/info';
		return $view;
	}

	private function _user_manufacture($row) {

		if(empty($this->cfg['dept'])) $this->cfg['dept'] = $this->User_model->get_dept_list();
		if(empty($this->cfg['team'])) $this->cfg['team'] = $this->User_model->get_team_list();
		if(empty($this->cfg['position'])) $this->cfg['position'] = $this->config->item( 'position_code' );
		if(empty($this->cfg['duty'])) $this->cfg['duty'] = $this->config->item( 'duty_code' );

		$row['dept_name'] = $this->cfg['dept'][$row['dept_code']];
		$row['team_name'] = $this->cfg['team'][$row['team_code']];
		$row['position_name'] = $this->cfg['position'][$row['position_code']];
		$row['duty_name'] = $this->cfg['duty'][$row['duty_code']];

		$row['service'] = (date_diff($row['join_date'], date('Y-m-d'))); //근속기간

		$row['biz_id'] = explode(',',$row['biz_id']);

		//사진
		$default_photo = '/images/common/no_photo_user_200.png';
		$photo = (file_exists($_SERVER['DOCUMENT_ROOT'].$row['photo']) && $row['photo'])?$row['photo']:$default_photo;
		$row['photo_path'] = $photo;

		//병역사항
		$row['military_info'] = @unserialize($row['military_info']);

		//보훈
		$row['veterans_info'] = @unserialize($row['veterans_info']);

		//장애
		$row['defect_info'] = @unserialize($row['defect_info']);

		//채용정보
		$row['recruit_info'] = @unserialize($row['recruit_info']);

		//긴급연락처
		$row['emergency_info'] = @unserialize($row['emergency_info']);
		return $row;
	}

	private function _user_info_basic() {
		$user_no = $this->uri->segment(3);
		$user_info = $this->User_model->get_info(array('no'=>$user_no));



		$this->data = array(
			'basic'=>$this->_user_manufacture($user_info)
		);

		// pre($this->data);
		$view = '/manage/user/info_basic';
		return $view;
	}

	private function _user_info_tab() {
		$user_no = $this->uri->segment(3);
		$tab_no = $this->uri->segment(4);
		// echo $tab_index;

		$user_info = $this->User_model->get_info(array('no'=>$user_no));
		$data = $this->_user_info_tab_set($tab_no);
		$this->data = array(
			'basic'=>$this->_user_manufacture($user_info)
		);

		$this->data = array_merge($this->data, $data);
		// pre($this->data);
		$view = '/manage/user/info_tab_'.$tab_no;
		return $view;
	}

	private function _user_info_tab_set($tab_no) {
		$data = array();
		switch($tab_no) {
			case '6':
				$dept_list = $this->User_model->get_dept_list();
				$position_list = $this->config->item( 'position_code' );
				$occupy_list =  $this->common_lib->get_code_item( '03' ); //직군
				$duty_list = $this->config->item( 'duty_code' );

				$data = array(
					'cfg'=>array(
						'dept'=>$dept_list,
						'duty'=>$duty_list,
						'occupy'=>$occupy_list,
						'position'=>$position_list,
						'biz'=>$this->session->userdata( 'ss_biz_list' )
					)
				);
			break;
			case '1':
				$data = array(
					'cfg'=>array(
						'householder'=>array('본인', '배우자', '부모', '자녀', '기타'),
						'bank'=>get_bank(),
						'blood'=>array('A','B','O','AB'),
						'military'=>array(
							'type'=>array('1'=>'군필','2'=>'미필','3'=>'면제','4'=>'특례','5'=>'대상아님'), //제대유형
							'honor'=>array('만기제대', '의가사제대', '의병제대', '소집해제', '불명예제대', '상이제대', '기타'), //전역사유
							'group'=>array('육군','해군','공군','해병','전경','의경','공익','기타'), //군별
							'rank'=>array('이병','일병','상병','병장','하사','중사','상사','원사','준위','소위','중위','대위','소령','중령','대령','준장','소장','중장','대장','기타') //계급
						)
					)
				);
			break;
			case '4':
				$data = array(
					'cfg'=>array(
						'recruit'=>array(
							'type'=>array('수시채용','특별채용','공개채용','사내추천','학교추천','재입사'),
							'job'=>array('사무직','현장근로')
						)
					)
				);
			break;

		}
		return $data;


	}
	/**
	 * 직원정보 등록
	 * @return [type] [description]
	 */
	public function _user_input() {

		// 부서정보
		$dept_list = $this->User_model->get_dept_list();
		$position_list = $this->config->item( 'position_code' );
		$duty_list = $this->config->item( 'duty_code' );

		$this->data = array (
			'cfg'=>array(
				'dept'=>$dept_list,
				'duty'=>$duty_list,
				'position'=>$position_list,
				'biz'=>$this->session->userdata( 'ss_biz_list' )
			)
		);
		$view = 'manage/user/input';
		return $view;
	}

	private function _user_lists() {
		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];

		$first = ($page - 1) * $limit;
		$position_list = $this->config->item( 'position_code' );
		$duty_list = $this->config->item( 'duty_code' );
		$user_status_list = $this->config->item( 'user_status' );

		$team_list = $this->User_model->get_team_list( $dept_code );
		$dept_list = $this->User_model->get_dept_list();

		$where = null;

		$result = $this->User_model->get_user_list( $first, $limit, $where );
		$total = $this->User_model->get_total();
		foreach ( $result as $i => $row ) {
			$no = $total - $first - $i;

			$list->rows[$i]['no'] = $row['user_id'];
			$list->rows[$i]['cell'] = array (
					$no,
					set_date_format( 'Y-m-d', $row['reg_date'] ),
					$row['user_id'],
					$row['name'],
					$dept_list[$row['dept_code']],
					$team_list[$row['team_code']],
					$position_list[$row['position_code']],
					$duty_list[$row['duty_code']],
					$row['tel'],
					$row['mobile'],
					$row['email'],
					$user_status_list[$row['status']]
			);
		}

		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		echo json_encode( $list );
	}




	public function check_dup() {}

	function team_list() {
		$list = $this->common_lib->get_team($dept_code);
		if($list) {
			return_json(true,'', $list);
		}
		else {
			return_json(false,'');
		}
	}



	public function get_team_json() {
		$dept_code = $this->input->post('dept_code');
		$biz_id = $this->input->post('biz_id');
		$this->load->library('common_lib');
		$list = $this->common_lib->get_team($dept_code, 1, $biz_id);

		if($list) {
			return_json(true,'', $list);
		}
		else {
			return_json(false,'');
		}
	}

	public function get_user_json() {
		$team_code = $this->input->post('team_code');
		$key_field = ($this->input->post('key'))?$this->input->post('key'):'user_id';
		$this->load->library('common_lib');
		$list = $this->common_lib->get_user($team_code, $key_field);
		if($list) {
			return_json(true,'', $list);
		}
		else {
			return_json(false,'');
		}
		// $team_code = $this->segs[3];
		// $list = $this->User_model->get_team_user( $team_code );
		// echo json_encode( $list );
	}

	public function get_item_json() {
		$parent_code = $this->segs[3];
		if (! empty( $parent_code )) $rs = $this->Manage_model->get_code_item( '', $parent_code, 'all' );
		$this->load->model('Treat_Model');
		$list = array();
		$cost = $this->Treat_Model->select_cost(array(), 'no, cost_origin');
		foreach($rs as $row) {
			$row['cost']=$cost[$row['etc']]['cost_origin'];
			$list[] = $row;
		}
		echo json_encode( $list );
	}



	private function _basic_main() {
		$this->page_title = "병원정보";

		$row = $this->Manage_model->get_hst_info();

		// 사업장 정보
		$result = $this->Manage_model->get_biz_list();
		foreach ( $result as $i => $biz_row ) {
			$list[$i]->biz_id = $biz_row['biz_id'];
			$list[$i]->biz_name = $biz_row['biz_name'];
			$list[$i]->biz_url = $biz_row['biz_url'];
		}

		$uid = $this->session->userdata('ss_user_id');

		$this->data = array (
			'row'=>$row,
			'list'=>$list,
			'auth_reset'=>in_array($uid, array('hjlee','stonek700','shpark'))
		);
		$view = 'manage/index';
		return $view;
	}


	private function _auth_main() {
		$this->page_title = "접근권한";

		$view = 'manage/auth/ip';
		return $view;
	}




	private function ip_lists() {

		$result = $this->Manage_model->get_ip_list();
		foreach ( $result as $i => $row ) {
			$no = $i+1;
			$list->rows[$i]['no'] = $row['seqno'];
			$list->rows[$i]['cell'] = array (
					$no,
					set_long_date_format( '-', $row['reg_date'] ),
					$row['ip'],
					$row['info'],
					$row['use_flag'],
					$row['seqno']
			);
		}
		echo json_encode( $list );
	}


	private function _access_main() {


		$this->page_title = "접근권한";

		$result = $this->Manage_model->get_menu_list();
		foreach ($result as $i=>$row) {
			$list[$i]->menu_seqno = $row['menu_seqno'];
			$list[$i]->main_menu = $row['main_menu'];
			$list[$i]->menu_title = $row['menu_title'];
			$list[$i]->url = $row['url'];
		}

		$this->data = array('list'=>$list);
		$view = 'manage/auth/access';
		return $view;
	}



	private function _access_list() {

		//$this->output->enable_profiler( TRUE );

		$menu_seqno = $this->segs[3];

		//전체부서
		$dept_list = $this->User_model->get_dept_list();

		//전체팀
		$team_list = $this->User_model->get_team_list();


		$result = $this->Manage_model->get_access_list($menu_seqno);
		foreach ( $result as $i => $row ) {
			$key = $row['menu_seqno'];
			${'access_' . $row['category']}[$row['valid_code']] = 'checked';
		}

		$this->data = array (
				'dept_list'=>$dept_list,
				'team_list'=>$team_list,
				'access_team'=>$access_team,
				'access_dept'=>$access_dept,
				'access_duty'=>$access_duty,
				'menu_seqno'=>$menu_seqno
		);

		$view = 'manage/auth/access_list';
		return $view;
	}

	/**
	 * 메뉴별 권한설정
	 * @return [type] [description]
	 */
	private function _auth_menu() {
		$this->page_title = "메뉴별 권한설정";


		$duty_list = $this->config->item( 'duty_code' );
		$menu = $this->config->item( 'hmis_menu' );
		$dept_list = $this->User_model->get_dept_list();

		$this->load->library('menu_lib');
		$tree = $this->menu_lib->set_tree('all');
		$biz_id = $this->session->userdata('ss_my_biz_list');
		$datum = array (
			'cfg'=>array(
				'duty'=>$duty_list,
				'dept'=>$dept_list,
				'biz_id'=>$biz_id,
				'tree'=>$tree
			)
		);

		$this->_render('auth/menu', $datum);
		// $view = 'manage/auth/menu';
		// return $view;
	}

	private function _get_menu() {
		$this->load->library('menu_lib');
		$menu_no = $this->input->post( 'menu_no' );
		$rs = $this->Manage_model->get_menu($menu_no);
		$menu_info = array_shift($rs);
		$menu_info['navigator'] = $this->menu_lib->get_navigation($menu_no);

		//권한정보
		if($menu_info['grant_dept']) {
			$cfg_biz = $this->Manage_model->get_biz_info('H000', 'Y' );


			$dept_biz = unserialize($menu_info['grant_dept_biz']);
			$grant_dept_info = $this->_set_user($menu_info['grant_dept']);

			foreach($grant_dept_info as $k=>$v) {
				if(array_filter($dept_biz[$k])) {
					$grant_dept_biz = array();
					foreach($dept_biz[$k] as $biz_id) {
						$grant_dept_biz[] = array(
							'id'=>$biz_id,
							'name'=>$cfg_biz[$biz_id]
						);
					}
				}
				else {
					$grant_dept_biz = null;
				}

				$grant_dept_info[$k] = array(
					'user'=>$v,
					'biz_id'=>implode(',',$dept_biz[$k]),
					'biz'=>$grant_dept_biz
				);
			}

			$menu_info['grant_dept_info'] = $grant_dept_info;
			// $menu_info['grant_dept_biz'] = $grant_dept_biz;
		}
		// pre($menu_info);

		return_json(true, '',$menu_info);
	}

	private function _team() {
		$dept_list = $this->User_model->get_dept_list();//전체부서
		$datum = array(
			'cfg'=>array(
				'dept'=>$dept_list
			)
		);
		$view = 'manage/team/list.html';
		$this->_render('team/list', $datum);
		// return $view;
	}


	function _team_list() {
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
				case 'team_name':
					$where['team_name LIKE']="%{$v}%";
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$biz_list = $this->session->userdata('ss_biz_list');

		$rs = $this->Manage_model->select_team_paging($where, $offset, $limit);
		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			$dept_list = $this->User_model->get_dept_list();//전체부서
			$count = $this->User_model->get_team_user_count();
			// pre($count);
			foreach($rs['list'] as $row) {
				$row['dept_name'] = $dept_list[$row['dept_code']];
				$row['biz_name'] = $biz_list[$row['biz_id']];
				$row['count_user'] = number_format($count[$row['team_code']]['cnt']);
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

	function _team_input() {
		$dept_list = $this->User_model->get_dept_list();//전체부서
		$team_code = $this->input->post('team_code');
		if($team_code) {
			$mode = 'update';
			$team_info = $this->User_model->get_team_row(array('team_code'=>$team_code));
		}
		else {
			$mode = 'insert';
			$team_info = array(
				'status'=>'1'
			);
		}

		$datum = array(
			'mode'=>$mode,
			'cfg'=>array(
				'biz'=>$this->session->userdata('ss_biz_list'),
				'dept'=>$dept_list
			),
			'rs'=>$team_info
		);

		$this->_render('team/input', $datum,'inc');
		// $view = 'manage/team/input.html';
		// return $view;
	}

	function _team_move() {
		$team_list = $this->User_model->get_team_list();//전체팀
		$this->data = array(
			'cfg'=>array('team'=>$team_list)
		);
		$view = 'manage/team/move.html';
		return $view;
	}

	private function _auth_group() {
		$this->data = array();
		$view = 'manage/auth/group.html';
		return $view;
	}

	function _auth_group_input() {

		$dept_list = $this->User_model->get_dept_list();//전체부서
		$no = $this->input->post('no');

		if($no) {
			$mode = 'update';
			$data = $this->Manage_model->select_group_row(array('no'=>$no));
			$data['users_list'] = $this->_set_user($data['users']);
		}
		else {
			$mode = 'insert';
			$data = array(
				'is_use'=>'Y',
				'type'=>'include'
			);
		}

		$this->data = array(
			'cfg'=>array(
				'dept'=>$dept_list,
				'mode'=>$mode
			),
			'data'=>$data
		);

		$view = 'manage/auth/group_input.html';
		return $view;
	}

	function _auth_group_list() {
		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','price_type', 'category_sub','block'))) continue;
			if($v == 'all' || !$v) continue;

			switch($k) {
				case 'word':
					$where["(group_code LIKE '%{$v}%' or comment LIKE '%{$v}%')"]=NULL;
				break;
				case 'is_use' :
					$where['is_use'] = $v;
				break;
				default :
					break;
			}
		}

		$rs = $this->Manage_model->select_group_paging($where, $offset, $limit);
		if($rs['count']['search'] > 0) {


			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				if($row['users']) {
					$row['users_list'] = $this->_set_user($row['users']);
				}

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

	private function _set_user($users) {
		$dept_list = $this->User_model->get_dept_list();
		$team_list = $this->User_model->get_team_list('','1','all');
		$grant_dept_arr = array_filter(explode(',',$users));
		$dept_list['all'] = '전체 부서';
		foreach($grant_dept_arr as $grant) {
			$tmp = array();
			list($dept, $team, $user) = explode('_',$grant);
			if($dept_list[$dept]) {
				array_push($tmp, $dept_list[$dept]);
			}
			else {
				continue;
			}

			if($team) {
				if(array_key_exists($team, $team_list)) {
					array_push($tmp, $team_list[$team]);
					if($user) {
						$user_info = $this->User_model->get_info(array('no'=>$user, 'status'=>1));
						if($user_info) {
							array_push($tmp, $user_info['name']);
						}
						else {
							$tmp = null;
						}
					}
				}
				else continue;
			}

			if(is_array($tmp)) {
				$grant_dept[$grant] = implode(' > ',$tmp);
			}
		}

		return $grant_dept;
	}

	function tree() {
		$tree = array();
		$tree['id']='0';
		$dept = $this->User_model->get_dept_list();


		foreach($dept as $dept_code=>$dept_name) {
			$team = $this->User_model->get_team_list($dept_code);
			unset($team_item);
			foreach($team as $team_code=>$team_name) {
				$user = $this->User_model->get_team_user($team_code, $dept_code);
				unset($user_item);
				foreach($user as $user_id=>$user_name) {
					$userdata = array(
						array('name'=>'type', 'content'=>'user')
					);
					$user_item[] = array('id'=>$user_id, 'text'=>$user_name, 'userdata'=>$userdata,  'im0'=>'blank.gif', 'imwidth'=>'0');
				}
				$userdata = array(
					array('name'=>'type', 'content'=>'team')
				);
				$team_item[] = array('id'=>'team_'.$team_code, 'text'=>$team_name, 'item'=>$user_item, 'userdata'=>$userdata);
			}

			$userdata = array(
				array('name'=>'type', 'content'=>'dept')
			);
			$dept_item[] = array('id'=>'dept_'.$dept_code, 'text'=>$dept_name, 'item'=>$team_item, 'userdata'=>$userdata);
		}
		$tree['item'] = $dept_item;

		echo json_encode($tree);
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "/manage/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}



	// 20170303 kruddo : 환자정보 > X 체크 환자 데이터 완전 삭제
	private function db_delete() {
		$this->_render('auth/db_delete');
	}

	public function db_delete_list_paging() {

		$p = $this->param;

		parse_str($this->input->post('search'), $assoc);
		$this->session->set_userdata('search',array_filter($assoc)); //검색데이터 세션처리

		//$page = $assoc['page'];
		$page = $this->input->post('page');
		$limit = $this->input->post('limit')?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		$where = array();
		$where_offset = array(
			'is_x'=>'Y'
		);


		$rs = $this->Patient_Model->select_patient_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {

			$manager = $this->common_lib->get_user();
			$team_list = $this->User_model->get_team_list(); //팀

			$query = $this->db->query("select patient_no, sum(amount_basic) amount_basic, sum(amount_unpaid) amount_unpaid, sum(paid_total) paid_total from patient p join patient_project pp
							on p.no=pp.patient_no where p.is_x='Y' and pp.is_delete='N' group by patient_no");
			$project_rs = $query->result_array();
			foreach($project_rs as $row){
				$project[$row["patient_no"]]["patient_no"] = $row['patient_no'];
				$project[$row["patient_no"]]["amount_basic"] = $row['amount_basic'];
				$project[$row["patient_no"]]["amount_unpaid"] = $row['amount_unpaid'];
				$project[$row["patient_no"]]["paid_total"] = $row['paid_total'];
			}



			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['team_name'] = $team_list[$row['manager_team_code']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['date_insert'] = substr($row['date_insert'],0,10);
				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);

				$row['amount_basic'] = $project[$row['no']]['amount_basic'];
				$row['amount_unpaid'] = $project[$row['no']]['amount_unpaid'];
				$row['paid_total'] = $project[$row['no']]['paid_total'];

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

		$user_no = $this->session->userdata('ss_user_no');
		$rs['count']['favorite'] = $this->Patient_Model->count_patient(array("CONCAT(',',favorite_user,',') LIKE '%,{$user_no},%'"=>NULL));


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		//$param['mode'] = 'excel';
		/*
		if($param['mode']=='excel') {
			$html = $this->_render('db_delete_list_paging', $return, 'inc', true);
			$this->_excel('환자정보 삭제',$html);
		}
		else{
			*/
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
		//}
	}

	public function db_list_delete() {

		//use Ifsnop\Mysqldump as IMysqldump;

		/*
		try {
			$dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=cmltd_ruddo', 'root', '1rlatjr))&$');
			$dump->start('dump.sql');
			return_json(true, "ok");
		} catch (\Exception $e) {
			//echo 'mysqldump-php error: ' . $e->getMessage();
			return_json(false, $e->getMessage());
		}
		*/



		// DB 백업
		//DBBackup();
		// DB 뱍옵


/*
		$DB_HOST = "localhost";
		$DB_USER = "root";
		$DB_PASS = "1rlatjr))&$";
		$DB_NAME = "cmltd_ruddo";


		$BACKUP_PATH = '/home/kakwon/';
		$BACKUP_NAME = 'DB_'.date("Ymd_His").'.sql.gz';
		$BACKUP_FILE = $BACKUP_PATH.$BACKUP_NAME;
		$DOWNLOAD_PATH = './'.$BACKUP_NAME;
		$command = "mysqldump -h $DB_HOST -u $DB_USER -p $DB_PASS $DB_NAME --opt | gzip > $BACKUP_FILE";
		//$command = "mysqldump --opt -h localhost -u root -p 1rlatjr))&$ cmltd_ruddo | gzip > /home/kakwon/DB_20170306_092030.sql.gz";
		//			"mysqldump --opt -h $dbhost -u $dbuser -p $dbpass ". "test_db | gzip > $backup_file";

		system($command);




		if(file_exists($DOWNLOAD_PATH)) {

			$filename = urlencode($BACKUP_NAME);

			header("Content-Type: application/octet-stream;");

			header("Content-Disposition: attachment; filename=$filename");

			header("Content-Transfer-Encoding: binary");

			header("Content-Length: ".(string)filesize($DOWNLOAD_PATH));

			header("Cache-Control: cache, must-revalidate");

			header("Pragma: no-cache");

			header("Expires: 0");

			readfile($DOWNLOAD_PATH);

			unlink($DOWNLOAD_PATH);
			return_json(true, '성공');

		}

		else {

			//echo "File not found.";
			return_json(false, '실패');

		}
*/




		/*
		$where = " and is_x='Y' and no=3501";

		//$query1 = "SELECT * FROM consulting_charge_log where cst_seqno in (select cst_seqno from patient where 1=1 ".$where.")";
		$this->db->query("delete FROM consulting_charge_log where cst_seqno in (select cst_seqno from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM consulting_contact where cst_seqno in (select cst_seqno from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM consulting_memo where cst_seqno in (select cst_seqno from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM log_change where cst_seqno in (select cst_seqno from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM log_cst_status where cst_seqno in (select cst_seqno from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_agree where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_appointment where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_chart where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_consulting where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_doctor where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_material where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_nurse where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_pay where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_photo where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_project where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_skin where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient_treat where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM db_info where db_seqno in (select db_seqno from consulting_info ci join patient p on ci.cst_seqno=p.cst_seqno where 1=1 ".$where.")");

		$this->db->query("delete FROM consulting_info where patient_no in (select no from patient where 1=1 ".$where.")");

		$this->db->query("delete FROM patient where 1=1 ".$where);


		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return_json(false, '삭제실패');
		} else {
			$this->db->trans_commit();
			return_json(true, '삭제성공', $command, $BACKUP_FILE);
		}
		*/

	}


	// 20170303 kruddo : 환자정보 > X 체크 환자 데이터 완전 삭제
}
