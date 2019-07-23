<?php
class HMIS_Model extends Model
{
	function HMIS_Model()
	{
		parent::Model();
	}
	
	function __get($name)
	{
		$CI =& get_instance();
		
		foreach (get_object_vars($CI) as $CI_object_name => $CI_object)
		{
			if (is_object($CI_object) && is_subclass_of(get_class($CI_object), 'CI_DB') && $CI_object_name == $name)
			{
				return $CI_object;
			}
		}
		
		$CI->$name = $CI->load->database($name, TRUE);
		
		return $CI->$name;
	}
}

/* End of file */