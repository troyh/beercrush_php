<?php
require_once('beercrush/oak.class.php');

$cgi_fields=array(
	"q"	=> array(type=>OAK::DATATYPE_TEXT),
	"dataset"	=> array(type=>OAK::DATATYPE_TEXT),
);

function oakMain($oak)
{
	global $cgi_fields;
	if ($oak->cgi_value_exists('q',$cgi_fields))
	{
		$doctypes=null;
		if ($oak->cgi_value_exists('dataset',$cgi_fields))
		{
			$dataset=$oak->get_cgi_value('dataset',$cgi_fields);
			if (!empty($dataset))
			{
				$doctypes=preg_split('/\s+/',$dataset);
			}
		}
		$results=$oak->query($oak->get_cgi_value('q',$cgi_fields),false,$doctypes);
		
		header("Content-Type: application/javascript");
		print $results;
	}
}

require_once 'beercrush/oak.php';
