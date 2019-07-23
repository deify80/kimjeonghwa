<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login_lib {

	protected $ci;
	public $session_var;

	public function __construct() {

		$this->ci =& get_instance();

		// get session variable
		// $cfg_common = $this->ci->config->item('common');
		// pre($cfg_common);
		$this->session_var = 'user_info';
	}

	/**
	 * 로그인 상태 반환
	 * @return boolean [description]
	 */
	public function is_logged_in() {
		return !empty($this->ci->session->userdata('logged_in'));
	}

	public function display_login($uri='') {
		if(!$this->get_login_infos()) {
			$redirect = ($uri)?$uri:uri_string();
			$_SESSION['redirect'] = $redirect;
			redirect('/login');
		}
		else {
		}
	}

	/**
	 * 로그인 정보 반환
	 * @return Array
	 */
	public function get_login_infos($key='') {
		$infos = $this->ci->session->userdata;


		if(isset($infos['ss_user_id']) && $infos['ss_user_id']) {
			if($key) {
				return isset($infos[$key]) ? $infos[$key] : NULL;
			}
			else {
				return $infos;
			}
		}
		return NULL;
	}

	function reset_infos($record) {
		$infos = $this->get_infos();
		$cookie = array_merge($infos, $record);
		$this->ci->session->set_userdata($this->session_var, $cookie);
	}

	function encryption($passwd) {
		return md5($passwd.ENCRYPTION_KEY);
	}
}

/* End of file Login.php */
/* Location: app/admin/libraries//Login.php */


