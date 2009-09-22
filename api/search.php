<?php
require_once('beercrush/oak.class.php');

$cgi_fields=array(
	"q"	=> array(type=>OAK::DATATYPE_TEXT),
);

function oakMain($oak)
{
	global $cgi_fields;
	if ($oak->cgi_value_exists('q',$cgi_fields))
	{
		$results=$oak->query($oak->get_cgi_value('q',$cgi_fields),FALSE);
		
		header("Content-Type: application/javascript");
		print $results;
	}
}

require_once 'beercrush/oak.php';
