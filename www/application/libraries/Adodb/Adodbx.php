<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Adodbx
{
	private $ci;
	function __construct()
	{
		if ( ! class_exists('ADONewConnection') )
		{
			require_once(BASEPATH.'libraries/adodb5/adodb.inc.php');
			require_once(BASEPATH.'libraries/adodb5/adodb-error.inc.php');
		}

		$this->ci =& get_instance();
		$this->_init_adodb_library($ci);
	}

	function _init_adodb_library(&$ci)
	{
        $db_var = false;
        $debug = false;
		$show_errors = true;
		$active_record = true;
		$db = NULL;
		if (!isset($dsn)) {
            // fallback to using the CI database file
        	include(APPPATH.'config/database.php');
            $group = 'default';
            $dsn = $db[$group]['dbdriver'].'://'.$db[$group]['username']
                   .':'.$db[$group]['password'].'@'.$db[$group]['hostname']
                   .'/'.$db[$group]['database'];
        }

		if ($show_errors) {
			require_once(BASEPATH.'libraries/adodb5/adodb-errorhandler.inc.php');
		}

		$this->ci->adodb = ADONewConnection($dsn);
		$this->ci->adodb->setFetchMode(ADODB_FETCH_ASSOC);
		$this->ci->adodb->Execute('SET NAMES UTF8');

		if ($db_var) {
            $this->ci->db =& $ci->adodb;
        }

		if ($active_record) {
			require_once(BASEPATH.'libraries/adodb5/adodb-active-record.inc.php');
			ADOdb_Active_Record::SetDatabaseAdapter($ci->adodb);
		}

		if ($debug) {
			$this->ci->adodb->debug = true;
		}
    }

	function ADOdb_Active_Record_Factory($classname, $tablename=null)
	{
		eval('class '.$classname.' extends ADOdb_Active_Record{}');

		if ($tablename != null) {
			return new $classname($tablename);
		}
		else {
			return new $classname;
		}

	}

	function GetInsertSQL($table, $record) {
		$sql = "SELECT * FROM {$table} WHERE no=-1";
		$rs = $this->ci->adodb->Execute($sql);
		$insertSQL  = $this->ci->adodb->GetInsertSQL($rs, $record);
		return $insertSQL;
	}

	function GetUpdateSQL($table, $record, $where) {
		$sql = "SELECT * FROM {$table} WHERE {$where}";
		$rs = $this->ci->adodb->Execute($sql);
		$updateSql = $this->ci->adodb->GetUpdateSQL($rs, $record);
		$result = $this->ci->adodb->Execute($updateSql);
		return $result;
	}

}



?>
