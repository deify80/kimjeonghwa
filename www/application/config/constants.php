<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

define('CI_VIEW_PATH', $_SERVER['DOCUMENT_ROOT']."/tmpl");
define('DOC_ROOT',$_SERVER['DOCUMENT_ROOT']);
define('TIME_YMDHIS', date('YmdHis'));
define('NOW', date('Y-m-d H:i:s'));
define('TODAY', date('Y-m-d'));
define('PERMANANT_DATE', '99999999999999');
define('SYSTEM_TITLE', '씨엠엘티디');
define('DS', '-');
define('COOKIE_TIME', '1800');

define('PERMANENT_MAX', '100'); //MYDB 최대갯수
define('PER_PAGE','15'); //페이지당 기본 출력수

define('APPOINTMENT_START', '09:00:00');
define('APPOINTMENT_END', '22:00:00');

define('DEV', (in_array($_SERVER['REMOTE_ADDR'], array('222.237.33.226','218.234.32.142')))?true:false);

define('VER',time());

/* End of file constants.php */
/* Location: ./application/config/constants.php */
