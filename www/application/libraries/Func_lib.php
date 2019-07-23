<?php
if ( ! defined('BASEPATH'))	exit('No direct	script access	allowed');
/*
set	up variable	and	mysql	for	bulletin board.
*/


class Func_lib {

	public function	__construct()
	{
		// 클래스 로드시 필요한 함수 실행
	}


	/**
	 * @brief
	**/
	function pr($s) {
		if (BMSLIB_DEBUG) {
			echo '<p><xmp style="margin:10px; padding:8px; background-color:#EFEFEF; color:#003D4C; font-family:Tahoma; font-size:11px;">';
			print_r($s);
			echo '</xmp></p>';
		}
	}

}