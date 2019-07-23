<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

if(!function_exists('array_column')) {
function array_column(array $array, $column_key, $index_key=null){
	$result = array();
	foreach($array as $arr) {
		if(!is_array($arr)) continue;

		if(is_null($column_key)) {
			$value = $arr;
		}
		else{
			$value = $arr[$column_key];
		}

		if(!is_null($index_key)) {
			$key = $arr[$index_key];
			$result[$key] = $value;
		}
		else {
			$result[] = $value;
		}
	}
	return $result;
}
}

?>