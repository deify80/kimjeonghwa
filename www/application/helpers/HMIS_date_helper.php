<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

function second_convert($sec) {
	if(is_numeric($sec)){
		$value = array(
			"years" => 0, 
			"days" => 0, 
			"hours" => 0,
			"minutes" => 0, 
			"seconds" => 0
		);

		if($sec >= 31556926){
			$value["years"] = floor($sec/31556926);
			$sec = ($sec%31556926);
		}
		if($sec >= 86400){
			$value["days"] = floor($sec/86400);
			$sec = ($sec%86400);
		}
		if($sec >= 3600){
			$value["hours"] = floor($sec/3600);
			$sec = ($sec%3600);
		}
		if($sec >= 60){
			$value["minutes"] = floor($sec/60);
			$sec = ($sec%60);
		}
		$value["seconds"] = floor($sec);
		return (array) $value;
	}else{
		return (bool) FALSE;
	}
}

if ( ! function_exists('date_diff')) {
	function date_diff($start, $end) {
		$diff = abs(strtotime($end) - strtotime($start));

		$return = array('years'=>'0', 'months'=>'0', 'days'=>'0');

		$return['years'] = floor($diff / (365*60*60*24));
		$return['months'] = floor(($diff - $return['years'] * 365*60*60*24) / (30.5*60*60*24));
		$return['days'] = floor(($diff - $return['years'] * 365*60*60*24 - $return['months']*30*60*60*24)/ (60*60*24));

		return $return;
	}
}

?>