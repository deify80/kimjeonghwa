<?php
function init() {
	$_SESSION['ver'] = 'mobile';
	$CI =& get_instance();
	if (!$CI->input->is_cli_request() &&!$CI->login_lib->is_logged_in() && !in_array($CI->uri->uri_string(), array('login', 'login_proc/login'))) {
		redirect('/login', 'refresh');
	}

}
