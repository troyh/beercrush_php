<?php
require_once('beercrush/couchdb.php');
require_once('beercrush/oak.class.php');

// Validate GET/POST data

global $conf_file;
$oak=new OAK($conf_file);

try
{
	if ($cgi_flags&OAK_CGI_REQUIRE_USERID)
	{
		// TODO: do authentication
	}

	$oak->load_cgi_fields();

	if ($oak->get_missing_field_count())
		throw new Exception($oak->get_missing_field_count()." required field(s) missing.");

	if ($oak->get_invalid_field_count())
		throw new Exception($oak->get_invalid_field_count()." invalid values for field(s).");
	
	oakMain($oak);
}
catch(Exception $x)
{
	print "Exception:".$x->getMessage();
}

?>
