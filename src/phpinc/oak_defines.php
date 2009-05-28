<?php

class OAK_CGI_FIELD
{
	var $m_name;
	var $m_flags;
	var $m_type;
	var $m_min;
	var $m_max;
	var $m_userfunc;
	
	function _construct($name,$flags,$type,$min,$max,$userfunc)
	{
		print "function _construct($name,$flags,$type,$min,$max,$userfunc)\n";
		$m_name			=$name;
		$m_flags		=$flag;
		$m_type			=$type;
		$m_min			=$min;
		$m_max			=$max;
		$m_userfunc		=$userfunc;
	}
};

define("OAK_FIELDFLAG_REQUIRED", 1);

define("OAK_DATATYPE_INT"  , 1);
define("OAK_DATATYPE_TEXT" , 2);
define("OAK_DATATYPE_FLOAT", 3);
define("OAK_DATATYPE_MONEY", 4);
define("OAK_DATATYPE_BOOL" , 5);

?>