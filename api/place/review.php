<?php
header("Cache-Control: no-cache");
require_once("OAK/oak.class.php");

$cgi_fields=array(
	"place_id"		=> array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_TEXT, validatefunc=>'Place::validatePlaceID' ),
	"rating"		=> array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"atmosphere"	=> array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"service"		=> array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"food"			=> array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"kidfriendly"	=> array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"comments"		=> array(type=>OAK::DATATYPE_TEXT),
);

function oakMain($oak)
{
	global $cgi_fields;
	
	$review=new OAKDocument('review');
	$review->user_id=$oak->get_user_id();
	$review->setID($review->type.':'.$oak->get_cgi_value('place_id',&$cgi_fields).':'.$review->user_id);

	// Get existing review, if there is one so that we can update just the parts added/changed in this request
	if ($oak->get_document($review->getID(),&$review)===true)
	{
		// Do nothing, it doesn't matter
	}

	// Give it this request's edits
	$oak->assign_cgi_values(&$review,$cgi_fields);

	// Store in db
	if ($oak->put_document($review->getID(),$review)!==true)
	{
		header("HTTP/1.0 500 Save failed");
	}
	else
	{
		header("HTTP/1.0 200 OK");
		header("Content-Type: application/json; charset=utf-8");
		$review->id=$review->_id;
		unset($review->_id);
		unset($review->_rev);
		print json_encode($review);
	}

}

require_once('OAK/oak.php');

?>