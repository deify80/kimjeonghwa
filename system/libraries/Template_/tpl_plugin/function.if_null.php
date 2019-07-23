<?php
function if_null($str, $empty_string="<span style='color:#8A8A8A;'>-</span>"){
	if(!$str) echo $empty_string;
	else echo $str;
}
?>

