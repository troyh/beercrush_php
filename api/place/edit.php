<?php
require_once 'beercrush/oak.class.php';
require_once 'beercrush/Place.class.php';

function validate_place_id($name,$value,$attribs,$converted_value,$oak)
{
	// The brewery_id must already exist
	$place_doc=new PlaceDocument;
	// If there's a doc for it, it's valid
	return $oak->get_document($converted_value,$place_doc);
}

$cgi_fields=array(
	"place_id"					=> array(flags=>OAK::FIELDFLAG_CGIONLY,type=>OAK::DATATYPE_TEXT, validatefunc=>validate_place_id ),
	"@attributes" => array(type=>OAK::DATATYPE_OBJ, properties=>array(
		"in_operation"			=> array(type=>OAK::DATATYPE_BOOL),
		"specializes_in_beer"	=> array(type=>OAK::DATATYPE_BOOL),
		"tied"					=> array(type=>OAK::DATATYPE_BOOL),
		"bottled_beer_to_go"	=> array(type=>OAK::DATATYPE_BOOL),
		"growlers_to_go"		=> array(type=>OAK::DATATYPE_BOOL),
		"kegs_to_go"			=> array(type=>OAK::DATATYPE_BOOL),
		"brew_on_premises"		=> array(type=>OAK::DATATYPE_BOOL),
		"taps"					=> array(type=>OAK::DATATYPE_BOOL),
		"casks"					=> array(type=>OAK::DATATYPE_BOOL),
		"bottles"				=> array(type=>OAK::DATATYPE_BOOL),
		"wheelchair_accessible"	=> array(type=>OAK::DATATYPE_BOOL),
		"music"					=> array(type=>OAK::DATATYPE_BOOL),
		"wifi"					=> array(type=>OAK::DATATYPE_BOOL),
	)),
	"name"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>1, maxlen=>200),
	"address" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"street"					=> array(type=>OAK::DATATYPE_TEXT , minlen=>1, maxlen=>200),
		"city"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>1, maxlen=>200),
		"state"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>2, maxlen=>2),
		"zip"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>5, maxlen=>10),
		"country"					=> array(type=>OAK::DATATYPE_TEXT , minlen=>2, maxlen=>100),
		"latitude"					=> array(type=>OAK::DATATYPE_FLOAT, min=>-180.0, max=>180),
		"longitude"					=> array(type=>OAK::DATATYPE_FLOAT, min=>-180.0, max=>180),
		"neighborhood"				=> array(type=>OAK::DATATYPE_TEXT),
	)),
	"description"	=> array(type=>OAK::DATATYPE_TEXT),
	"established"	=> array(type=>OAK::DATATYPE_INT, min=>1500, max=>(int)date('Y')),
	"hours"			=> array(type=>OAK::DATATYPE_OBJ, properties => array(
		"open"    => array(type=>OAK::DATATYPE_TEXT),
		"tour"    => array(type=>OAK::DATATYPE_TEXT),
		"tasting" => array(type=>OAK::DATATYPE_TEXT),
	)),
	"kid_friendly" => array(type=>OAK::DATATYPE_BOOL),
	"parking" 	   => array(type=>OAK::DATATYPE_BOOL),
	"phone"		   => array(type=>OAK::DATATYPE_PHONE),
	"restaurant" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"reservations" 			=> array(type=>OAK::DATATYPE_BOOL),
		"alcohol" 				=> array(type=>OAK::DATATYPE_BOOL),
		"accepts_credit_cards" 	=> array(type=>OAK::DATATYPE_BOOL),
		"good_for_groups" 		=> array(type=>OAK::DATATYPE_BOOL),
		"outdoor_seating" 		=> array(type=>OAK::DATATYPE_BOOL),
		"smoking" 				=> array(type=>OAK::DATATYPE_BOOL),
		"food_description" 		=> array(type=>OAK::DATATYPE_TEXT),
		"menu_uri" 				=> array(type=>OAK::DATATYPE_URI),
		"price_range" 			=> array(type=>OAK::DATATYPE_TEXT),
		"attire" 				=> array(type=>OAK::DATATYPE_TEXT),
		"waiter_service" 		=> array(type=>OAK::DATATYPE_BOOL),
	)),
	"tour_info" => array(type=>OAK::DATATYPE_TEXT),
	"uri" => array(type=>OAK::DATATYPE_URI),
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

		if ($oak->cgi_value_exists('place_id',$cgi_fields)) // Editing existing place
		{
			$place_id=$oak->get_cgi_value('place_id',$cgi_fields);
			// Get existing place, if there is one so that we can update just the parts added/changed in this request
			$place=new PlaceDocument;
			if ($oak->get_document($place_id,&$place)!==true)
				throw new Exception("No existing place $place_id");
		}
		else  // Adding a new place
		{
			$place=PlaceDocument::createPlace($oak->get_cgi_value('name',$cgi_fields));
			
			// See if this place already exists
			if ($oak->get_document($place->getID(),&$place)===true)
			{
				throw new Exception($place->getID()." already exists");
			}
		}

		// Give it this request's edits
		$oak->assign_cgi_values(&$place,$cgi_fields);
	
		// Store in db
		if ($oak->put_document($place->getID(),$place)!==true)
		{
			header("HTTP/1.0 500 Save failed");
		}
		else
		{
			$oak->log('Edited:'.$place->getID());
			
			$xmlwriter=new XMLWriter;
			$xmlwriter->openMemory();
			$xmlwriter->startDocument();
			
			$oak->write_document($place,$xmlwriter);

			$xmlwriter->endDocument();
			print $xmlwriter->outputMemory();
		}
	}
}

require_once 'beercrush/oak.php';

?>