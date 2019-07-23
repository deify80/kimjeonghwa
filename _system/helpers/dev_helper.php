<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// ------------------------------------------------------------------------

if ( ! function_exists('pre'))
{
	function pre($arr)
	{
		echo "<pre>";
		print_R($arr);
		echo "</pre>";
	}
}

/**
 * JSON 형태로 리턴
 */
if(!function_exists('return_json')) {
	function return_json($success, $msg='', $data=array()) {
		$return = array(
			'success'=>$success,
			'msg'=>$msg,
			'data'=>$data
		);

		echo json_encode($return);
		exit;
	}
}

if(!function_exists('format')) {
	function format($type, $text) {
		switch($type) {
			case 'mobile':
				$text = str_replace('-','',$text);
				$formatted = preg_replace("/(0(?:2|[0-9]{2}))([0-9]+)([0-9]{4}$)/", "\\1-\\2-\\3", $text);
			break;
			case 'birthday':
				$text = str_replace('-','',$text);
				$formatted = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2}$)/", "\\1-\\2-\\3", $text);
			break;
			default:
				$formatted = $text;
			break;
		}
		return $formatted;
	}
}

if(!function_exists("array_column")) {
	function array_column($array,$column_name) {
		return array_map(function($element) use($column_name){return $element[$column_name];}, $array);
	}
}

if(!function_exists("mb_substr_replace")) {
	function mb_substr_replace($str, $start=0, $length=2, $replace_str='*') {
		$str_length = mb_strlen($str, "EUC-KR");
		if($str_length<=$length) return $str;

		$str_new = '';
		if($start>0) $str_new.=str_repeat($replace_str, $start);
		$str_new.=mb_substr($str, $start, $length, "EUC-KR");
		$str_new.=str_repeat($replace_str, $str_length-($start+$length));
		return $str_new;
	}
}

if(!function_exists("get_random")) {
	function get_random($length){
		$string	= array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l','m', 'n', 'o', 'p', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
		shuffle($string);
		$random = '';
		for($i=0;$i<$length;$i++) {
			$rand_key = array_rand($string);
			$random .= $string[$rand_key];
		}

		return $random;
	}
}
