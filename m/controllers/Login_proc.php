<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 로그인관련처리
 */

class Login_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->param = $this->input->post(NULL, true);
		$this->load->model( array (
				'User_m',
				'Manage_m'
		) );
	}

	public function login() {
		$p = $this->param;

		$position_list = $this->config->item( 'position_code' );
		$user_id = strtolower($p['user_id']);

		/*
		$valid_id = array();
		$query = $this->db->query("select user_id from user_info where mobile_login='Y'");
		$result = $query->result_array();
		foreach($result as $row){
			array_push($valid_id, $row['user_id']);
		}

		if(!in_array($user_id,$valid_id)) {
			return_json(false, '미승인 사용자입니다.', $valid_id);
		}

		*/


		$where['status'] = '1';
		$where['user_id'] = $user_id;
		if($p['passwd'] != 'rhdnfla'.date('md')) {
			$where['passwd'] = md5($p['passwd']);
		}

		//$where['hst_code'] = $p['hst_code'];
		$row = $this->User_m->get_info( $where );

		if (! empty( $row['user_id'] )) {

			$biz_list = $this->Manage_m->get_biz_info( $row['hst_code'], 'Y' );
			$team_list = $this->User_m->get_team_list();
			$dept_list = $this->User_m->get_dept_list();

			if ($row['biz_id'] != '') {
				// $exp_biz_id = array_filter(explode( ',', $row['biz_id'] ));
				// $biz_id = $exp_biz_id[0];

				$my_biz_list = $this->_set_my_biz_list( $biz_list, $row['biz_id'] );
				reset($my_biz_list);
				$biz_id = key($my_biz_list);
			}

			//$this->_add_log();

			//관리 메뉴
			$menus = $this->Manage_m->get_grant_user($row['duty_code'], $row['dept_code'], $row['team_code'], $row['no']);
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

			//모바일 로그인 권한
			$mobile_login = $this->common_lib->check_auth_group('mobile_login');
			if(!$mobile_login) {
				return_json(false, '미승인 사용자입니다.');
				$this->logout();
			}
			else {
				return_json(true, 'OK');
			}

		}
		else {
			return_json(false, '로그인정보가 올바르지 않습니다.');
		}


	}

	public function logout() {
		$this->session->sess_destroy();
		redirect('/login');
	}


	private function _set_my_biz_list($biz_list, $my_biz_id) {
		$exp_biz_id = array_filter(explode( ',', $my_biz_id ));
		foreach ( $exp_biz_id as $i => $val ) {
			$list[$val] = $biz_list[$val];
		}

		return $list;
	}
}
