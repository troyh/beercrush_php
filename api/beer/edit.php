<?php
require_once 'beercrush/oak.class.php';

function validate_brewery_id($name,$value,$attribs,$converted_value,$oak)
{
	// The brewery_id must already exist
	$brewery_doc=new stdClass;
	// If there's a doc for it, it's valid
	return $oak->get_document($converted_value,$brewery_doc);
}

$cgi_fields=array(
	"abv"						=> array(type=>OAK::DATATYPE_FLOAT, min=>0.0, max=>100.0),
	"availability"				=> array(type=>OAK::DATATYPE_TEXT),
	"beer_id"					=> array(type=>OAK::DATATYPE_TEXT),
	"bjcp_style_id"				=> array(type=>OAK::DATATYPE_TEXT, validatefunc=>validate_beer_bjcp_style_id),
	"brewery_id"				=> array(type=>OAK::DATATYPE_TEXT, validatefunc=>validate_brewery_id ),
	"calories_per_ml"			=> array(type=>OAK::DATATYPE_INT, min=>0, max=>1000),
	"description"				=> array(type=>OAK::DATATYPE_TEXT),
	"grains"					=> array(type=>OAK::DATATYPE_TEXT),
	"hops"						=> array(type=>OAK::DATATYPE_TEXT),
	"ibu"						=> array(type=>OAK::DATATYPE_INT, min=>0, max=>1000),
	"ingredients"				=> array(type=>OAK::DATATYPE_TEXT),
	"name"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>1, maxlen=>200),
	"otherings"					=> array(type=>OAK::DATATYPE_TEXT),
	"yeast"						=> array(type=>OAK::DATATYPE_TEXT),
);

require_once 'beercrush/Beer.class.php';


function oakMain($oak)
{
	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		header("HTTP/1.0 201 Login required");
	}
	else
	{
		$beer=new Beer;

		if ($oak->cgi_value_exists('beer_id')) // Editing existing beer
		{
			$beer_id=$oak->get_cgi_value('beer_id');
			// Get existing beer, if there is one so that we can update just the parts added/changed in this request
			if ($oak->get_document($beer_id,&$beer)!==true)
				throw new Exception("No existing beer $beer_id");
		}
		else  // Adding a new beer
		{
			$beer=Beer::createBeer($oak->get_cgi_value('brewery_id'),$oak->get_cgi_value('name'));
			
			// See if this beer already exists
			if ($oak->get_document($beer->getID(),&$beer)===true)
			{
				throw new Exception($beer->getID()." already exists");
			}
		}

		// Give it this request's edits
		$oak->assign_values(&$beer);
	
		// Store in db
		if ($oak->put_document($beer->getID(),$beer)!==true)
		{
			header("HTTP/1.0 201 Internal error");
		}
		else
		{
			$xmlwriter=new XMLWriter;
			$xmlwriter->openMemory();
			$xmlwriter->startDocument();
			
			$oak->write_document($beer,$xmlwriter);

			$xmlwriter->endDocument();
			print $xmlwriter->outputMemory();
		}
	}
}

require_once 'beercrush/oak.php';

?>