<?php  if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 배열변수 가독성향상
 */
if(!function_exists('pre')) {
	function pre($arr) {
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
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


if(!function_exists('if_empty')) {
	function if_empty($value, $default='<span class="f-null">미입력</span>', $format='%s') {
		$value = trim($value);
		return (empty($value))?$default:sprintf($format,$value);
	}
}

if(!function_exists('if_empty_int')) {
	function if_empty_number($value, $default='<span class="f-null">0</span>', $format='%s') {
		$value = trim($value);
		return (empty($value))?$default:sprintf($format,number_format($value));
	}
}

if(!function_exists('array_sort')) {
	function array_sort($arr_data, $column, $sort=SORT_ASC, $recursive=false) {  // sort = SORT_DESC  or  SORT_ASC
		$org_data = $arr_data;
		foreach($arr_data as $key => $val) if($val[$column] != false) $tmp_data[]=$val;
		for($i=0; $i<count($tmp_data); $i++) $sortarr[]=$tmp_data[$i][$column];
		if($recursive) @array_multisort($sortarr, $sort, $org_data);
		return $org_data;
	}
}


function write_log($log_path, $log_content) {
	$dir = dirname($log_path);
	if(!is_dir($dir)) {
		mkdir($dir,0777);
	}
	if(!($fp = fopen($log_path, "a+"))) return 0;

	ob_start();
	echo "\n\n--------------------------------\n";
	echo "DATE :".date('Y-m-d H:i:s')."\n";
	echo "IP :".$_SERVER['REMOTE_ADDR']."\n";
	echo "--------------------------------\n";
	print_r($log_content);
	$ob_msg = ob_get_contents();
	ob_clean();

	if(fwrite($fp, " ".$ob_msg."\n") === FALSE) {
		fclose($fp);
		return 0;
	}
	fclose($fp);
	return 1;
}

function format_mobile($mobile) {
	$mobile = str_replace('-','',$mobile);
	return preg_replace("/(0(?:2|[0-9]{2}))([0-9]+)([0-9]{4}$)/", "\\1-\\2-\\3", $mobile);
}
?>
