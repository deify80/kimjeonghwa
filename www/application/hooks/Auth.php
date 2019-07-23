<?php
/**
 * 작성 : 2014.10.17
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Auth {

	public function check_login() {
		$CI = & get_instance();
		//print_R($_GET);
		switch($_GET['ver']) {
			case 'mobile':
			case 'pc':
				$_SESSION['ver'] = $_GET['ver'];
			break;
			default:
			break;
		}
		if($CI->agent->is_mobile() && !in_array($CI->uri->segment(1), array('api')) && $_SESSION['ver']!='pc') {
			redirect( 'http://m.cmltd.kr' );
		}

		if ($_SERVER['PHP_SELF'] == '/index.php/' || (in_array( $CI->uri->rsegment( 1 ), array ('login','front', 'api') )) || (in_array( $CI->uri->rsegment( 2 ), array ('bbs_sinmungo') )) || (in_array( $CI->uri->rsegment(3), array ('bbs_sinmungo') )) ) {

		} else {

			if (! $CI->session->userdata( 'logged_in' )) {

				if (! in_array( $CI->uri->rsegment( 1 ), array (
						'login',
						'request'
				) )) {

					redirect( '/' );
				}
			}
		}

		if ($CI->input->post('scope')) {
			$CI->layout_lib->setScope($CI->input->post('scope'));
		}

		$CI->menu = $CI->menu_lib->set_tree($CI->session->userdata('ss_menu_grant'));
		$grantkey = $CI->menu_lib->get_grantkey(MK_3);

		$CI->grant_biz = $CI->menu_lib->get_biz(MK_3, $grantkey);

		$hst_info = $CI->Manage_model->get_hst_info();
		$x = ($hst_info['is_reset']=='Y')?true:false;
		define('X',$x);
		$cash_x = ($hst_info['is_cash']=='Y')?true:false;
		define('CASHX',$cash_x);
	}



	// 사용안함
	/*
	function check_access() {

		$CI = & get_instance();
		$CI->load->model( 'Manage_model' );

		if ($CI->session->userdata( 'logged_in' )) {

			// url 호출
			$result = $CI->Manage_model->get_menu_list();
			foreach ( $result as $i => $row ) {
				$access_url[$row['menu_seqno']] = $row['url'];
			}

			// $access_dept
			$result = $CI->Manage_model->get_access_list();
			foreach ( $result as $i => $row ) {
				$key = $row['menu_seqno'];
				${'access_' . $row['category']}[$key][$row['valid_code']] = $row['valid_code'];
			}
		}

		$is_valid = true;

		// 해당 조건안에 해당이 안될 경우 에러메세지 출력
		foreach ( $access_url as $key => $url ) {
			if (strpos($CI->uri->uri_string(), $url) !== false) {
				$is_valid = false;

				if (in_array($CI->session->userdata( 'ss_duty_code' ), array('9'))) {
					$is_valid = true;
				} else if (in_array( $CI->session->userdata( 'ss_dept_code' ), $access_dept[$key] )) {
					$is_valid = true;
				} else if (in_array( $CI->session->userdata( 'ss_team_code' ), $access_team[$key] )) {
					$is_valid = true;
				} else if (in_array( $CI->session->userdata( 'ss_duty_code' ), $access_duty[$key] )) {
					$is_valid = true;
				}

				break;
			}
		}

		if ($is_valid == false) {
			echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=" . $CI->config->item( 'charset' ) . "\">";
			echo "<script type='text/javascript'>alert('접근권한이 없습니다.');";
			echo "history.go(-1);";
			echo "</script>";
			exit();
		}
	}
	*/


}
?>
