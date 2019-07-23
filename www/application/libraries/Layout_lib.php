<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Layout_lib {
	private $ci;
	private $default;
	private $assign = array();
	var $scope = 'LAYOUT';

	public  function __construct() {
		$this->ci =& get_instance();
		$this->controller = strtolower($this->ci->router->fetch_class());
		$this->func = strtolower($this->ci->router->fetch_method());
	}


	public function setScope($scope) {
		$this->scope = $scope;
	}

	private function init($layout) {
		$this->ci->yield = FALSE;

		if($layout == 'default') {
			$_SESSION['grant_biz'] = $this->ci->common_lib->grant_menu_by_biz();
			$menu_tree = $this->ci->menu;//$this->ci->menu_lib->set_tree($this->ci->session->userdata('ss_menu_grant'));
			// $get_grantkey = $this->ci->menu_lib->get_grantkey(MK_3);
		}
		else {
			$_SESSION['grant_biz'] = $this->ci->session->userdata('ss_biz_id');
		}

		$menu_left = $menu_tree[MK_1];
		$menu_top = $this->ci->menu_lib->top;

		$title = ($this->controller=='main' && $this->func=='msg')?'Dashboard':$this->ci->menu_lib->cfg_menu[MK_3]['name'];
		$assign_default = array(
			'page'=>array(
				'querystring' => $_SERVER['QUERY_STRING'],
				'controller'=> $this->controller,
				'func'=> $this->func,
				'title'=>$title,
				'navigator'=>$this->ci->menu_lib->get_navigation(MK_3, 'string'),
				'grant_biz'=>$_SESSION['grant_biz'],
				'is_main'=>($_SERVER['PHP_SELF'] == '/index.php/main/msg')?'yes':'no'
			),
			'path'=>array(
				'image'=>'/views/images'
			),
			'login' => array(
				'is'=> $this->ci->session->userdata('logged_in'),
				'info'=>$this->ci->session->userdata
			),
			'device'=>($this->ci->agent->is_mobile())?'mobile':'pc',
			'menu'=> array(
				'top'=>$menu_top,
				'left'=>$menu_left
			),
			'auth'=>array(
				'banner'=>$this->ci->common_lib->check_auth_group('hide_banner'),
				'sms_limit'=>$this->ci->common_lib->check_auth_group('sms_limit'),
				'ex_bizid'=>$this->ci->common_lib->check_auth_group('ex_bizid'),
				'view'=>$this->ci->menu_lib->get_grant(),
				'mobile_pc'=>$this->ci->common_lib->check_auth_group('mobile_pc')
			)
		);

		$assign_default = array('layout'=>$assign_default);
		$this->assign_($assign_default);
	}

	public function default_($body,$assign=array(), $layout='default') {

		$this->init($layout);

		if(count($assign)>0) {
			$this->assign_($assign);
		}

		$config_layout = $this->ci->config->item('layouts');
		if(array_key_exists($layout, $config_layout)) {
			$bundle = $config_layout[$layout];
		}
		else {
			//존재하지 않는 레이아웃 경고
			// display_warning('board','board_no_contents');
		}

		//사업장 체크
		/*
		$grant_biz = $_SESSION['grant_biz'];
		if(in_array($grant_biz, array('none')) && $body!='/main/index.html') {
			$body = '_include/error.html';
			$this->assign_( array(
				'msg'=>'이 사업장에 대한 권한이 없습니다.'
			));
		}
		*/

		$define = array_change_key_case(array_merge(array('BODY'=>$body), $bundle), CASE_UPPER);
		$this->ci->template_->define($define);


		$this->ci->template_->assign($this->assign);
	}

	public function define_($key,$path) {
		$this->ci->template_->define(array(
			$key=>$path
		));
	}

	public function assign_($assign=array()) {
		if(count($assign)>0) {
			$this->assign = array_merge($this->assign, $assign);
		}
	}

	public function print_($fetch=false) {
		//header("Content-Type:text/html");
		if($fetch) {
			$html = $this->ci->template_->fetch($this->scope);
			return $html;
		}
		else {
			$this->ci->template_->print_($this->scope);
		}

	}

	public function print_error($title, $msg) {
		$assign = array(
			'title'=>$title,
			'msg'=>$msg
		);
		$this->default_('/common/error.html',$assign);
		$this->print_();
	}

	function fetch_($tpl, $assign) {
		$this->init();
		$this->ci->template_->define('fetch',$tpl);
		$assign = array_merge($this->assign, $assign);
		$this->ci->template_->assign($assign);
		return $this->ci->template_->fetch('fetch');
	}
}
?>
