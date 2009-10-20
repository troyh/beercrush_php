<?php
require_once 'beercrush/oak.class.php';
require_once 'beercrush/Brewery.class.php';

function validate_brewery_id($name,$value,$attribs,$converted_value,$oak)
{
	// If there's a doc for it, it's valid
	$brewery_doc=new BreweryDocument;
	return $oak->get_document($converted_value,$brewery_doc);
}

$cgi_fields=array(
	"brewery_id"				=> array(flags=>OAK::FIELDFLAG_CGIONLY,type=>OAK::DATATYPE_TEXT, validatefunc=>validate_brewery_id ),
	"name"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>1, maxlen=>200),
	"description"				=> array(type=>OAK::DATATYPE_TEXT),
	"address" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"street"					=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>200),
		"city"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>200),
		"state"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>0),
		"zip"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>10),
		"country"					=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>100),
		"latitude"					=> array(type=>OAK::DATATYPE_FLOAT, min=>-180.0, max=>180),
		"longitude"					=> array(type=>OAK::DATATYPE_FLOAT, min=>-180.0, max=>180),
	)),
	"established"	=> array(type=>OAK::DATATYPE_INT, min=>1500, max=>(int)date('Y')),
	"phone"		   	=> array(type=>OAK::DATATYPE_PHONE, minlen=>0),
	"uri" 			=> array(type=>OAK::DATATYPE_URI),
	"togo" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"bottles" 	=> array(type=>OAK::DATATYPE_BOOL),
		"growlers" 	=> array(type=>OAK::DATATYPE_BOOL),
		"kegs" 		=> array(type=>OAK::DATATYPE_BOOL),
	)),
	"tourinfo"					=> array(type=>OAK::DATATYPE_TEXT),
	"tasting"					=> array(type=>OAK::DATATYPE_TEXT),
	"hours"						=> array(type=>OAK::DATATYPE_TEXT),
);



function oakMain($oak)
{
	global $cgi_fields;

	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		$oak->request_login();
	}
	else
	{
		if ($oak->cgi_value_exists('brewery_id',$cgi_fields)) // Editing existing brewery
		{
			$brewery_id=$oak->get_cgi_value('brewery_id',$cgi_fields);
			// Get existing brewery, if there is one so that we can update just the parts added/changed in this request
			$brewery=new BreweryDocument;
			if ($oak->get_document($brewery_id,&$brewery)!==true)
				throw new Exception("No existing brewery $brewery_id");
		}
		else if ($oak->cgi_value_exists('name',$cgi_fields)) // Adding a new brewery
		{
			$brewery=BreweryDocument::createBrewery($oak->get_cgi_value('name',$cgi_fields));
			
			// See if this brewery already exists
			if ($oak->get_document($brewery->getID(),&$brewery)===true)
			{
				header("HTTP/1.0 409 Duplicate brewery name");
				print json_encode($brewery);
				exit;
			}
		}
		else
			throw new Exception('Brewery name required to create a brewery');

		// Give it this request's edits
		$oak->assign_cgi_values(&$brewery,$cgi_fields);
	
		// Store in db
		if ($oak->put_document($brewery->getID(),$brewery)!==true)
		{
			header("HTTP/1.0 500 Save failed");
		}
		else
		{
			$oak->log('Edited:'.$brewery->getID());
			
			print json_encode($brewery);
		}
	}
}

require_once 'beercrush/oak.php';

?>