<?php
function checked($v, $v2, $checked='checked') {
	if(is_array($v2)) {
		if(in_array($v, $v2)) return $checked;
		else return '';
	}
	else {
		if($v==$v2) return $checked;
		else return '';
	}
}
?>
