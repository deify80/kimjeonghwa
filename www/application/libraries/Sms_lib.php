<?php
/**
 * 작성 : 2014.12.04
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Sms_lib {
	var $host = 'agent1.kssms.kr';
	var $smsid = 'cmltdsms';
	var $pass = 'eoqkr!!';



	function sms_send($mtype, $phone, $msg, $callback, $upfile, $reservetime, $subject, $etc1, $etc2, $reserve_chk) {
		$param[] = "id=" . $this->smsid;
		$param[] = "pass=" . $this->pass;
		$param[] = "type=" . $mtype;
		$param[] = "reservetime=" . $reservetime;
		$param[] = "reserve_chk=" . $reserve_chk;
		$param[] = "phone=" . $phone;
		$param[] = "callback=" . $callback;
		$param[] = "msg=" . $msg;
		$param[] = "upfile=" . $upfile;
		$param[] = "subject=" . $subject;
		$param[] = "etc1=" . $etc1;
		$param[] = "etc2=" . $etc2;
		$str_param = implode( "&", $param );

		$path = ($mtype == "mms") ? "/proc/RemoteMms.html" : "/proc/RemoteSms.html";

		$fp = @fsockopen( $this->host, 80, $errno, $errstr, 30 );
		$return = "";

		if (! $fp) die( $_err . $errstr . $errno );
		else {
			fputs( $fp, "POST " . $path . " HTTP/1.1\r\n" );
			fputs( $fp, "Host: " . $host . "\r\n" );
			fputs( $fp, "Content-type: application/x-www-form-urlencoded\r\n" );
			fputs( $fp, "Content-length: " . strlen( $str_param ) . "\r\n" );
			fputs( $fp, "Connection: close\r\n\r\n" );
			fputs( $fp, $str_param . "\r\n\r\n" );
			while ( ! feof( $fp ) )
				$return .= fgets( $fp, 4096 );
		}
		fclose( $fp );

		$temp_array = explode( "\r\n\r\n", $return );
		$sms_data = mb_convert_encoding( $temp_array[1], 'utf8', 'euckr' );

		return $sms_data;
	}
}
