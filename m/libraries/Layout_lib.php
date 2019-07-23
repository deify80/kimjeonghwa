<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Layout_lib {
	private $ci;
	private $default;
	private $assign = array();

	var $controller;
	var $func;

	public  function __construct() {

		$this->ci =& get_instance();
		$this->controller = strtolower($this->ci->router->fetch_class());
		$this->func = strtolower($this->ci->router->fetch_method());

	}

	private function init() {
		// phpinfo();
		$is_login = $this->ci->login_lib->is_logged_in();
		$login_info = $this->ci->login_lib->get_login_infos();



		$assign_default = array(
			'page'=>array(
				'querystring' => $_SERVER['QUERY_STRING'],
				'controller'=> $this->controller,
				'func'=> $this->func,
				'grant_biz' => $this->ci->session->userdata('ss_biz_id'),
				'url'=>$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"],
			),
			'device'=>($this->ci->agent->is_mobile())?'mobile':'pc',
			'path'=>array(
				'assets'=>'/views/assets',
				'data'=>'/data'
			),
			'login' => array(
				'is'=> $is_login,
				'info'=>$this->ci->session->userdata
			)
		);


		//pre($assign_default);
		$assign_cfg = array(
			'basic'=>$this->ci->config->item('basic'),
			'company'=>$this->ci->config->item('ru_company')
		);

		$assign_default = array('layout'=>$assign_default, 'cfg'=>$assign_cfg);
		$this->assign_($assign_default);

	}

	public function set_page($path) {
		$path = strtolower($path);
		list($this->controller, $this->func) = explode('/',$path);
		// $this->controller = strtolower($controller);
		// $this->func = $func);
	}

	public function default_($body,$assign=array(), $layout='default') {

		$this->init();

		if(count($assign)>0) {
			$this->assign_($assign);
		}

		$config_layout = $this->ci->config->item('layout');

		if(array_key_exists($layout, $config_layout)) {
			$bundle = $config_layout[$layout];
		}
		else {
			//존재하지 않는 레이아웃 경고
			// display_warning('board','board_no_contents');
		}



		$msg_type = (isset($this->ci->msg_type))?$this->ci->msg_type:'normal';
		if(in_array($msg_type, array('error','warning'))) {
			$body = 'common/'.$msg_type.'.html';
			$this->assign_( array(
				'msg'=>$this->ci->msg
			));
		}


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
			$html = $this->ci->template_->fetch('LAYOUT');
			return $html;
		}
		else {
			$this->ci->template_->print_('LAYOUT');
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
