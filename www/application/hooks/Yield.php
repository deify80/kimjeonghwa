<?php if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Yield2
{
	function do_yield() {
		global $OUT;
		$CI = & get_instance();
		$output = $CI->output->get_output();

		$CI->yield = (isset( $CI->yield ) && !$CI->input->is_ajax_request()) ? $CI->yield : FALSE;
		$CI->layout = isset($CI->layout) ? $CI->layout : 'default_layout';
		$CI->page_title = isset( $CI->page_title ) ? $CI->page_title : '';

		if ($CI->yield === TRUE) {
			//메뉴관련 설정
			$menu_tree = $CI->menu_lib->set_tree($CI->session->userdata('ss_menu_grant'));
			$menu_href = $CI->config->item( 'cmltd_href' );
			$this_href = '/'.$CI->uri->uri_string();
			$route = $menu_href[$this_href];
			if($route) {
				$mk = $route[2];
				$CI->session->set_userdata('mk',$mk);

				//권한체크
				$grant = explode(',',$CI->menu_lib->cfg_menu[$mk]['sync_grant']);
				array_push($grant, $mk);
				foreach($grant as $k) {
					$grant_view = (in_array($k, explode(',',$CI->session->userdata('ss_menu_grant'))))?true:false; //권한체크
					if($grant_view) break;
				}
			}
			else {

				$mk = $CI->session->userdata('mk');

				// 권한체크
				if($this_href=='/main/msg') $grant_view = true; //홈 권한은 모두에게 부여
				else {
					$this_href = substr($this_href, 0, strripos($this_href,'/'));

					$rs = $CI->menu_lib->get_menu_row(array("href LIKE '{$this_href}%'"=>null), 'sync_grant');

					$sync_grant = explode(',',$rs['sync_grant']);
					if(in_array($mk, $sync_grant)) {
						$route = $CI->menu_lib->cfg_menu[$mk]['route'];
						$grant_view = (in_array($mk,$sync_grant))?true:false;
					}
					else {
						$grant_view = false;
						$menu_grant = explode(',',$CI->session->userdata('ss_menu_grant'));

						foreach($sync_grant as $m) {
							if(in_array($m, $menu_grant)) {

								$route = $CI->menu_lib->cfg_menu[$m]['route'];
								$grant_view = true;
								break;
							}
						}
					}
				}
			}

			define('MK_1', $route[0]);
			define('MK_2', $route[1]);
			define('MK_3', $route[2]);

			$page_title =  $CI->menu_lib->cfg_menu[MK_3]['name']; //메뉴명
			$page_width =  $CI->menu_lib->cfg_menu[MK_3]['width']; //페이지 너비
			$page_navigator = $CI->menu_lib->get_navigation(MK_3, 'string'); //메뉴네비게이션
			$CI->menu  = $menu_tree;
			$CI->grant_view = $grant_view;


			if (! preg_match( '/(.+).html$/', $CI->layout )) {
				$CI->layout .= '.html';
			}

			$requested = CI_VIEW_PATH. '/_layouts/' . $CI->layout;
			$requested_left = CI_VIEW_PATH. '/_layouts/left_menu.html';

			$layout = $CI->load->file( $requested, true );
			$left = $CI->load->file( $requested_left, true );

			$view = str_replace( '{yield}', $output, $layout );
			$view = str_replace( '{page_title}', $page_title, $view );
			$view = str_replace( '{page_width}', $page_width, $view );
			$view = str_replace( '{left}', $left, $view );
			$view = str_replace( '{page_navigator}', $page_navigator, $view );
		} else {
			$view = $output;
		}

		$OUT->_display( $view );
	}
}
?>
