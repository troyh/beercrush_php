<?php
header("Cache-Control: no-cache");
require_once 'OAK/oak.class.php';

function validate_place_id($name,$value,$attribs,$converted_value,$oak)
{
	// The brewery_id must already exist
	$place_doc=new OAKDocument('');
	// If there's a doc for it, it's valid
	return $oak->get_document($converted_value,$place_doc);
}

function makeID($s)
{
	$id=preg_replace('/[^a-zA-Z0-9]+/','-',$s);
	$id=preg_replace('/--+/','-',$id);
	$id=preg_replace('/^-+/','',$id);
	$id=preg_replace('/-+$/','',$id);
	return 'place:'.$id;
}

$cgi_fields=array(
	"place_id"				=> array(flags=>OAK::FIELDFLAG_CGIONLY,type=>OAK::DATATYPE_TEXT, validatefunc=>validate_place_id ),
	"name"					=> array(type=>OAK::DATATYPE_TEXT , minlen=>1, maxlen=>200),
	"in_operation"			=> array(type=>OAK::DATATYPE_BOOL),
	"specializes_in_beer"	=> array(type=>OAK::DATATYPE_BOOL),
	"tied"					=> array(type=>OAK::DATATYPE_BOOL),
	"brew_on_premises"		=> array(type=>OAK::DATATYPE_BOOL),
	"description"			=> array(type=>OAK::DATATYPE_TEXT),
	"established"			=> array(type=>OAK::DATATYPE_INT, min=>1500, max=>(int)date('Y')),
	"beer_selection" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"taps"					=> array(type=>OAK::DATATYPE_BOOL),
		"casks"					=> array(type=>OAK::DATATYPE_BOOL),
		"bottles"				=> array(type=>OAK::DATATYPE_BOOL),
	)),
	"address" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"street"					=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>200),
		"city"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>200),
		"state"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>0),
		"zip"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>10),
		"country"					=> array(type=>OAK::DATATYPE_TEXT , minlen=>0, maxlen=>100),
		"latitude"					=> array(type=>OAK::DATATYPE_FLOAT, min=>-180.0, max=>180.0),
		"longitude"					=> array(type=>OAK::DATATYPE_FLOAT, min=>-180.0, max=>180.0),
		"neighborhood"				=> array(type=>OAK::DATATYPE_TEXT),
	)),
	"hours"			=> array(type=>OAK::DATATYPE_OBJ, properties => array(
		"open"    => array(type=>OAK::DATATYPE_TEXT),
		"tour"    => array(type=>OAK::DATATYPE_TEXT),
		"tasting" => array(type=>OAK::DATATYPE_TEXT),
	)),
	"kid_friendly" 			=> array(type=>OAK::DATATYPE_BOOL),
	"parking" 	   			=> array(type=>OAK::DATATYPE_BOOL),
	"phone"		   			=> array(type=>OAK::DATATYPE_PHONE),
	"price"		   			=> array(type=>OAK::DATATYPE_INT, min=>1, max=>5),
	"restaurant" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"reservations" 			=> array(type=>OAK::DATATYPE_BOOL),
		"alcohol" 				=> array(type=>OAK::DATATYPE_BOOL),
		"accepts_credit_cards" 	=> array(type=>OAK::DATATYPE_BOOL),
		"good_for_groups" 		=> array(type=>OAK::DATATYPE_BOOL),
		"outdoor_seating" 		=> array(type=>OAK::DATATYPE_BOOL),
		"smoking" 				=> array(type=>OAK::DATATYPE_BOOL),
		"food_description" 		=> array(type=>OAK::DATATYPE_TEXT),
		"menu_uri" 				=> array(type=>OAK::DATATYPE_URI),
		"price_range" 			=> array(type=>OAK::DATATYPE_INT, min=>0, max=>4),
		"attire" 				=> array(type=>OAK::DATATYPE_TEXT),
		"waiter_service" 		=> array(type=>OAK::DATATYPE_BOOL),
	)),
	"togo" => array(type=>OAK::DATATYPE_OBJ, properties => array(
		"bottles"		=> array(type=>OAK::DATATYPE_BOOL),
		"growlers"		=> array(type=>OAK::DATATYPE_BOOL),
		"kegs"			=> array(type=>OAK::DATATYPE_BOOL),
	)),
	"wheelchair_accessible"	=> array(type=>OAK::DATATYPE_BOOL),
	"music"					=> array(type=>OAK::DATATYPE_BOOL),
	"wifi"					=> array(type=>OAK::DATATYPE_BOOL),
	"tour_info" => array(type=>OAK::DATATYPE_TEXT),
	"uri" => array(type=>OAK::DATATYPE_URI),
	"placetype" => array(type=>OAK::DATATYPE_TEXT),
	"placestyle" => array(type=>OAK::DATATYPE_TEXT),
);




function oakMain($oak)
{
	global $cgi_fields;

	$place=new OAKDocument('place');

	if ($oak->cgi_value_exists('place_id',$cgi_fields)) // Editing existing place
	{
		$place_id=$oak->get_cgi_value('place_id',$cgi_fields);
		// Get existing place, if there is one so that we can update just the parts added/changed in this request
		if ($oak->get_document($place_id,&$place)!==true)
			throw new Exception("No existing place $place_id");
	}
	else  // Adding a new place
	{
		$name=$oak->get_cgi_value('name',$cgi_fields);

		$bUniqueID=false;

		for ($attempt=0;$attempt<20;++$attempt)
		{
			switch ($attempt)
			{
				case 0:
					$id=makeID($name);
					break;
				case 1:
					$id=makeID($name.' '.$oak->get_cgi_value('address:city',$cgi_fields));
					break;
				case 2:
					$id=makeID($name.' '.$oak->get_cgi_value('address:city',$cgi_fields).' '.$oak->get_cgi_value('address:state',$cgi_fields));
					break;
				default:
					$id=makeID($name.' '.$oak->get_cgi_value('address:city',$cgi_fields).' '.$oak->get_cgi_value('address:state',$cgi_fields).' '.$attempt);
					break;
			}

			// See if this place already exists (use a temp variable so we don't overwrite our $place doc if this ID does already exist)
			$tmp_place=new OAKDocument('');
			if ($oak->get_document($id,&$tmp_place)===false)
			{
				$bUniqueID=true;
				$place->setID($id);
				$place->type='place';
				break;
			}
		}
		
		if ($bUniqueID==false)
		{
			header("HTTP/1.0 409 Unable to create unique id");
			exit;
		}

		$place->meta->cuser=$oak->get_user_id(); // Record user who created this place
	}

	// Give it this request's edits
	$oak->assign_cgi_values(&$place,$cgi_fields);

	// For these booleans, they can be true, false or non-existent, so delete them
	if (isset($_POST['kid_friendly']) && empty($_POST['kid_friendly']))
		unset($place->kid_friendly);
	if (isset($_POST['restaurant:outdoor_seating']) && empty($_POST['restaurant:outdoor_seating']))
		unset($place->restaurant->outdoor_seating);
	if (isset($_POST['wifi']) && empty($_POST['wifi']))
		unset($place->wifi);
	if (isset($_POST['togo:bottles']) && empty($_POST['togo:bottles']))
		unset($place->togo->bottles);
	if (isset($_POST['togo:growlers']) && empty($_POST['togo:growlers']))
		unset($place->togo->growlers);
	if (isset($_POST['togo:kegs']) && empty($_POST['togo:kegs']))
		unset($place->togo->kegs);

	// Store in db
	if ($oak->put_document($place->getID(),$place)!==true)
	{
		header("HTTP/1.0 500 Save failed");
	}
	else
	{
		$oak->log('Edited:'.$place->getID());
		
		header('Content-Type: application/json; charset=utf-8');
		$place->id=$place->_id;
		unset($place->_id);
		unset($place->_rev);
		unset($place->{"@attributes"});
		print json_encode($place);
	}

}

require_once 'OAK/oak.php';

?>