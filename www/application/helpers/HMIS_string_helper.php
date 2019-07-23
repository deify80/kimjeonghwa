<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


function set_special_char($str, $char) {
	if (trim( $str ) == "") return $char;
	else return $str;
}


function set_null($str) {
	if ($str == "") return NULL;
	else return $str;
}

function set_blank($str) {
	if (is_null($str) || $str == 0) return '';
	else return $str;
}

function set_encodes($pwd) {
	$tmp = $pwd;
	$data = "";
	$tmp1 = "";
	$tmp2 = "";
	$Dcdata = "L/Q~W`!@dO#$%e^P&f*G(lN)b_-hY+8=Mw4|Zgc<a>J?I[2{C]6}0i,AF.;p:BVu";
	$pdata = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-";
	$dedata = "";
	for($i = 0; $i < strlen( $tmp ); $i ++) {
		$tmp1 = substr( $tmp, $i, 1 );
		for($k = 0; $k < 64; $k ++) {
			$tmp2 = substr( $pdata, $k, 1 );
			if ($tmp1 == $tmp2) $dedata = $dedata . substr( $Dcdata, $k, 1 );
		}
	}

	return $dedata;
}

/*
 * 암호화된 스트링을 해독하여 리턴
 */
function set_decodes($pwd) {
	$tmp = $pwd;
	$tmp1 = "";
	$tmp2 = "";
	$endata = "";
	$Dcdata = "L/Q~W`!@dO#$%e^P&f*G(lN)b_-hY+8=Mw4|Zgc<a>J?I[2{C]6}0i,AF.;p:BVu";
	$pdata = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-";
	$endata = "";
	for($i = 0; $i < strlen( $tmp ); $i ++) {
		$tmp1 = substr( $tmp, $i, 1 );
		for($k = 0; $k < 64; $k ++) {
			$tmp2 = substr( $Dcdata, $k, 1 );
			if ($tmp1 == $tmp2) $endata = $endata . substr( $pdata, $k, 1 );
		}
	}
	return $endata;
}


/**
 * 공백표시
 *
 * @param
 *        	$str
 * @return unknown_type
 */
function set_whitespace($str) {
	$str = str_replace( " ", "&nbsp;", $str );
	return $str;
}


function set_number($str) {
	$str = str_replace( ",", "", $str );
	return $str;
}


function set_date_format2($delimiter, $date) {
	if ($date != "") $newDate = substr( $date, 0, 4 ) . $delimiter . substr( $date, 4, 2 ) . $delimiter . substr( $date, 6, 2 );
	return $newDate;
}


function set_long_date_format($delimiter, $date) {
	$newDate = substr( $date, 0, 4 ) . $delimiter . substr( $date, 4, 2 ) . $delimiter . substr( $date, 6, 2 ) . " " . substr( $date, 8, 2 ) . ":" . substr( $date, 10, 2 ) . ":" . substr( $date, 12, 2 );
	return $newDate;
}


function set_hour_format($delimiter, $date) {
	$newDate = substr( $date, 0, 4 ) . $delimiter . substr( $date, 4, 2 ) . $delimiter . substr( $date, 6, 2 ) . " " . substr( $date, 8, 2 ) . "시";
	return $newDate;
}


function set_minute_format($delimiter, $date) {
	if ($date != "") $newDate = substr( $date, 0, 4 ) . $delimiter . substr( $date, 4, 2 ) . $delimiter . substr( $date, 6, 2 ) . " " . substr( $date, 8, 2 ) . "시" . " " . substr( $date, 10, 2 ) . "분";
	return $newDate;
}

function set_date_format($format, $date) {
	return date($format, strtotime($date));
}

function set_timestamp($date) {
	return strtotime(set_date_format('Y-m-d H:i:s', $date));
}

function is_today($date) {
	$is_today = (substr($date, 0,8)==date('Ymd'))?true:false;
	return $is_today;
}

function set_today($date) {
	$mk = strtotime($date);
	if(date('Ymd', $mk) == date('Ymd')) return date('H:i', $mk);
	else return date('Y-m-d', $mk);
}
/**
 * 랜덤숫자
 *
 * @param
 *        	$min
 * @param
 *        	$max
 * @return unknown_type
 */
function number_rand($min = 0, $max = 9) {
	mt_srand( ( double ) microtime() * 1000000 );
	$number = mt_rand( $min, $max );
	return $number;
}


function cut_string_han($str, $len, $chn, $checkmb = false, $tail = '') {
	preg_match_all( '/[\xE0-\xFF][\x80-\xFF]{2}|./', $str, $match ); // target for BMP
	$m = $match[0];
	$slen = strlen( $str ); // length of source string
	$tlen = strlen( $tail ); // length of tail string
	$mlen = count( $m ); // length of matched characters
	if ($slen <= $len) return $str;
	if (! $checkmb && $mlen <= $len) return $str;
	$ret = array ();
	$count = 0;
	for($i = 0; $i < $len; $i ++) {
		$count += ($checkmb && strlen( $m[$i] ) > 1) ? 2 : 1;
		if ($count + $tlen > $len) break;
		$ret[] = $m[$i];
	}
	return join( '', $ret ) . $chn;
}


function change_name_han($str, $chn, $checkmb = false) {
	preg_match_all( '/[\xE0-\xFF][\x80-\xFF]{2}|./', $str, $match ); // target for BMP
	$m = $match[0];
	$len = count( $m );

	$ret = array ();
	$count = 0;
	for($i = 0; $i < $len; $i ++) {
		$count += ($checkmb && strlen( $m[$i] ) > 1) ? 2 : 1;
		if ($i == 0) {
			$ret[] = $m[$i];
		} else if ($i == 1 && $len == 2) {
			$ret[] = $chn;
			break;
		} else if ($i == $len - 1) {
			$ret[] = $m[$i];
		} else {
			$ret[] = $chn;
		}
	}
	return join( '', $ret );
}


function tostring($text) {
	return iconv( 'UTF-16LE', 'UHC', chr( hexdec( substr( $text[1], 2, 2 ) ) ) . chr( hexdec( substr( $text[1], 0, 2 ) ) ) );
}


function urlutfchr($text) {
	return urldecode( preg_replace_callback( '/%u([[:alnum:]]{4})/', 'tostring', $text ) );
}


function utf8_euckr(&$item, $key, $prefix = '') {
	if (is_array( $item )) array_walk( $item, 'utf8_euckr' );
	else $item = iconv( 'UTF-8', 'CP949', $item );
}

// 한글 코드페이지[949] => UTF-8로 변환 AJAX에서 유니코드 문자 오류
function euckr_utf8(&$item, $key, $prefix = '') {
	if (is_array( $item )) array_walk( $item, 'euckr_utf8' );
	else $item = iconv( 'CP949', 'UTF-8', $item );
}


function tel_check($tel_no, $delimiter1 = '-', $delimiter2 = '-') {
	$temp1 = strlen( $tel_no );
	if ($temp1 == 8) {
		$ex_num = substr( $tel_no, 0, 4 );
		$telNum = substr( $tel_no, - 4 );

		return $ex_num . $delimiter2 . $telNum;
	} elseif ($temp1 < 9) return $tel_no;

	$l2 = substr( $tel_no, 0, 2 );
	$l3 = substr( $tel_no, 0, 3 );

	$exac_len = 3;
	if ($l2 == "02") $exac_len = 2;
	if ($l3 == "050") $exac_len = 4;

	$l_code = substr( $tel_no, 0, $exac_len );
	$ex_num = substr( $tel_no, $exac_len, ($temp1 - $exac_len - 4) );
	$telNum = substr( $tel_no, - 4 );

	return $l_code . $delimiter1 . $ex_num . $delimiter2 . $telNum;
}


function get_hour() {
	for($i = 0; $i < 24; $i ++) {
		$list[str_pad( $i, 2, '0', STR_PAD_LEFT )] = str_pad( $i, 2, '0', STR_PAD_LEFT ) . "시";
	}

	return $list;
}


function get_minute() {
	for($i = 0; $i < 60; $i ++) {
		$list[str_pad( $i, 2, '0', STR_PAD_LEFT )] = str_pad( $i, 2, '0', STR_PAD_LEFT ) . "분";
	}

	return $list;
}


function get_second() {
	for($i = 0; $i < 60; $i ++) {
		$list[str_pad( $i, 2, '0', STR_PAD_LEFT )] = str_pad( $i, 2, '0', STR_PAD_LEFT ) . "초";
	}
	return $list;
}


function get_search_type_date() {
	$today = time();
	// $today = strtotime('2018-07-12'); //테스트

	$w = date('w', $today);
	$search_date['today'] = date( "Y-m-d", $today);
	$search_date['yesterday'] = date( "Y-m-d", strtotime('-1 day'));
	$search_date['weekday'] = date( "Y-m-d", strtotime("-{$w} days", $today)); //지난주 금요일부터~
	$search_date['pre_week_start'] = date("Y-m-d", strtotime("-1 week", strtotime($search_date['weekday'])));
	$search_date['pre_week_end'] = date("Y-m-d", strtotime("+6 days", strtotime($search_date['pre_week_start'])));
	$search_date['pre_month_start'] = date("Y-m-01", strtotime('first day of -1 month'));
	$search_date['pre_month_end'] =  date("Y-m-t", strtotime($search_date['pre_month_start']));
	$search_date['cur_month'] = date( "Y-m-01");
	$search_date['cur_month_end'] = date("Y-m-t", strtotime($search_date['cur_month']));					// 20170221 kruddo : 현재달 마지막날 구하기
	$search_date['weekday_db'] = date( "Y-m-d", strtotime("-".(($w+2)%7)." days", $today));
	$search_date['pre_week_end_db'] = date("Y-m-d", strtotime("-1 day", strtotime($search_date['weekday_db'])));
	$search_date['pre_week_start_db'] = date("Y-m-d", strtotime("-1 week", strtotime($search_date['weekday_db'])));
	return $search_date;
}


function get_bank() {
	$bank_list['001'] = 'KB국민은행';
	$bank_list['002'] = '우리은행';
	$bank_list['003'] = '신한은행';
	$bank_list['004'] = '하나은행';
	$bank_list['005'] = '스탠다드차타드은행';
	$bank_list['006'] = '한국씨티은행';
	$bank_list['007'] = '외환은행';
	$bank_list['008'] = '대구은행';
	$bank_list['009'] = '부산은행';
	$bank_list['010'] = '광주은행';
	$bank_list['011'] = '경남은행';
	$bank_list['012'] = '전북은행';
	$bank_list['013'] = '제주은행';
	$bank_list['014'] = '농협';
	$bank_list['015'] = '수협';
	$bank_list['016'] = '한국산업은행';
	$bank_list['017'] = '기업은행';
	$bank_list['018'] = '수출입은행';
	$bank_list['019'] = '신협';
	$bank_list['020'] = '우체국';
	$bank_list['021'] = '새마을금고';
	$bank_list['022'] = '산림조합';
	$bank_list['023'] = '저축은행';

	return $bank_list;
}


function get_card() {
	$card_list['001'] = '롯데카드';
	$card_list['002'] = '신한카드';
	$card_list['003'] = '비씨카드';
	$card_list['004'] = '현대카드';
	$card_list['005'] = '삼성카드';
	$card_list['006'] = 'KB국민카드';
	$card_list['007'] = '우리카드';
	$card_list['008'] = '하나카드(구,외환)';
	$card_list['009'] = '하나카드(구,하나SK)';
	$card_list['010'] = 'NH카드';
	$card_list['011'] = '수협카드';
	$card_list['012'] = '광주카드';
	$card_list['013'] = '전북카드';
	$card_list['014'] = '제주카드';
	$card_list['015'] = '저축은행카드';
	$card_list['016'] = '우체국체크카드';

	return $card_list;
}

// 20170328 kruddo - 카드종류 추가(경영지원>회계팀>카드정보)
function get_cardkind(){
	$card_kindlist['001'] = '현금카드';
	$card_kindlist['002'] = '법인카드';
	$card_kindlist['003'] = '개인카드';
	$card_kindlist['004'] = '복지카드';

	return $card_kindlist;
}
// 20170328 kruddo - 카드종류 추가(경영지원>회계팀>카드정보)

function output_excel($excel_title) {
	header( "Content-Type: application/vnd.ms-excel" );
	header( "Content-Disposition: attachment; filename=" . $excel_title . ".xls" );
	Header( "Cache-Control: cache, must-revalidate" );
	header( "Pragma: no-cache" );
	header( "Expires: 0" );
	echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
	echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'>";
}


function trim_all(&$array) {
	$array = trim( $array );
}


function set_blind($type, $string) {
	$CI = & get_instance();

	if (in_array($CI->session->userdata( 'ss_team_code' ), array('30', '32'))) return $string;

	if ($type == "jumin") {
		$new_string = substr_replace( $string, '**', 0, 2 );
		$new_string = substr_replace( $new_string, '****', - 4 );
	} else if ($type == "phone") {

		if (strlen( $string ) == 0) return '';

		$string = preg_replace("/[^0-9]/", "", $string);
		$new_string = preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/", "\\1-****-\\3", $string);

	} else if ($type == "name") {
		$new_string = change_name_han( $string, '*' );
	}

	return $new_string;
}


function set_current_time() {
	$date = null;
	$date['date'] = date( 'Y-m-d' );
	$date['hour'] = date( 'H' );
	$date['minute'] = date( 'i' );
	$date['second'] = date( 's' );

	return $date;
}


function set_sale_count($value) {
	$reset_value = $value;
	$minus_value = $value - intval( $value );
	if ($minus_value == 0) $reset_value = intval( $value );

	return $reset_value;
}

// 경고메세지를 경고창으로
function alert($msg = '', $url = '') {
	$CI = & get_instance();

	if (! $msg) $msg = '올바른 방법으로 이용해 주십시오.';

	echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=" . $CI->config->item( 'charset' ) . "\">";
	echo "<script type='text/javascript'>alert('" . $msg . "');";
	if (! $url) echo "history.go(-1);";
	echo "</script>";
	if ($url) goto_url( $url );
	exit();
}

// 경고메세지 출력후 창을 닫음
function alert_close($msg) {
	$CI = & get_instance();

	echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=" . $CI->config->item( 'charset' ) . "\">";
	echo "<script type='text/javascript'> alert('" . $msg . "'); window.close(); </script>";
	exit();
}

// 해당 url로 이동
function goto_url($url) {
	$CI = & get_instance();

	$temp = parse_url( $url );
	if (empty( $temp['host'] )) {
		$CI = & get_instance();
		$url = ($temp['path'] != '/') ? 'http://' . $url : 'http://' . $CI->config->item( 'base_url' ) . RT_PATH;
	}
	echo "<script type='text/javascript'> location.replace('" . $url . "'); </script>";
	exit();
}


function dead_line($reg_date) {

	$year = substr( $reg_date, 0, 4 );
	$day = substr( $reg_date, 6, 2 );
	$hour = substr( $reg_date, 8, 2 );
	$minute = substr( $reg_date, 10, 2 );
	$month = substr( $reg_date, 4, 2 );
	$second = substr( $reg_date, 12, 2 );
	$limit_date = mktime( $hour, $minute+30, $second, $month, $day, $year );
	$cur_date = mktime();

	$diff = $limit_date -$cur_date;
	$h = ( int ) ($diff / 3600);
	$m = ( int ) ($diff / 60) - $h * 60;
	$s = ( int ) ($diff) - $m * 60 - $h * 3600;
	return str_pad( $h, 2, 0, STR_PAD_LEFT ) . ':' . str_pad( $m, 2, 0, STR_PAD_LEFT ) . ':' . str_pad( $s, 2, 0, STR_PAD_LEFT );
}


function remove_array($ary_ori, $ary_del) {
	foreach ( $ary_ori as $k => $v ) {
		foreach ( $ary_del as $v2 ) {
			if ($k == $v2) {
				unset( $ary_ori[$k] );
			}
		}
	}

	return $ary_ori;
}


function remove_special($string) {
	if ($string == '') return $string;
	else return preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", $string);
}

/*
if(!function_exists("json_encode")){
	function json_encode($a=false){
		if(is_null($a)) return 'null';
		if($a === false) return 'false';
		if($a === true) return 'true';
		if(is_scalar($a)){
			if(is_float($a)) return floatval(str_replace(",", ".", strval($a)));
			if(is_string($a) || is_int($a)){
				 $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
			} else {
				return $a;
			}
		}
		$isList = true;
		for($i=0, reset($a); $i<count($a); $i++, next($a)){
			if(key($a) !== $i){
				$isList = false;
				break;
			}
		}
		$result = array();
		if($isList){
			foreach($a as $v) $result[] = json_encode($v);
			return '[' . join(',', $result) . ']';
		} else{
			foreach($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
			return '{' . join(',', $result) . '}';
		}
	}
}
*/

if (!function_exists('json_encode')) {
	function json_encode($content) {
		$CI = get_instance();
		$CI->load->library('Services_JSON','','json_lib');
		return $CI->json_lib->encode($content);
	}
}


if (!function_exists('json_decode')) {
	function json_decode($content, $assoc=false) {
		$CI = get_instance();
		if ($assoc) {
			$CI->load->library('Services_JSON', 16, 'json_lib');
		}
		else {
			$CI->load->library('Services_JSON', '', 'json_lib');
		}
		return $CI->json_lib->decode($content);
	}
}


function get_week_date($date) {
	$yy = substr($date, 0, 4);
	$mm = substr($date, 6, 2);
	$dd = substr($date, 8, 2);
	$weeks = date( "w", mktime( 12, 12, 12, $mm, $dd, $yy ) );
	$day['start'] = date('Ymd', mktime( 0, 0, 0, $mm, $dd - $weeks + 1, $yy ));
	$day['end'] = date('Ymd', mktime( 23, 59, 59, $mm, $dd + (7 - $weeks), $yy ));

	return $day;
}





/**************************/
function getWeek($t) {
  //Date Format: YYYY-MM-DD
  $s = explode("-",$t);
  $k = date("D", mktime(0, 0, 0, $s[1], 1, $s[0])); //해당월 1일은 무슨 요일인가
  switch($k) {
    //PHP 5.1.0 이하
    case "Sun" : $f = 0; break;
    case "Mon" : $f = 1; break;
    case "Tue" : $f = 2; break;
    case "Wed" : $f = 3; break;
    case "Thu" : $f = 4; break;
    case "Fri" : $f = 5; break;
    case "Sat" : $f = 6; break;
  }
  $d = date("D", mktime(0, 0, 0, $s[1], $s[2], $s[0])); //요일(영문:Mon)
  switch($d) {
    case "Sun" : $m = "일"; break;
    case "Mon" : $m = "월"; break;
    case "Tue" : $m = "화"; break;
    case "Wed" : $m = "수"; break;
    case "Thu" : $m = "목"; break;
    case "Fri" : $m = "금"; break;
    case "Sat" : $m = "토"; break;
  }
  $r = array();
  $r[] = $s[0]; //년
  $r[] = ceil($s[1]); //월
  $r[] = ceil((ceil($s[2])+$f)/7); //몇째주
  $r[] = $m;

  return $r;
}


function toWeekNum($timestamp) {
     $w = date('w', mktime(0,0,0, date('n',$timestamp), 1, date('Y',$timestamp)));
     return ceil(($w + date('j',$timestamp) -1) / 7);
}
