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
		$results=$oak->query($oak->get_cgi_value('q',$cgi_fields),true,$doctypes);
		// Add in brewery info for beers
		foreach ($results->response->docs as &$doc)
		{
			if (substr($doc->id,0,5)=='beer:')
			{
				list($type,$brewery_id,$beer_id)=explode(':',$doc->id);
				$brewery_doc=new OAKDocument('');
				if ($oak->get_document('brewery:'.$brewery_id,&$brewery_doc)===true)
				{
					$doc->brewery=new stdClass;
					$doc->brewery->name=$brewery_doc->name;
					$doc->brewery->id=$brewery_doc->_id;
				}
			}
		}
		
		header("Content-Type: application/javascript; charset=utf-8");
		print json_encode($results);
	}
}

require_once 'beercrush/oak.php';
