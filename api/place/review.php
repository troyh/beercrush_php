<?php
require_once("beercrush/oak.class.php");

$cgi_fields=array(
	"place_id"		=> array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_TEXT, validatefunc=>'Place::validatePlaceID' ),
	"rating"		=> array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_INT, min=>0, max=>5),
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
			$oak->write_document_json($review);
		}
	}

}

require_once('beercrush/oak.php');

?>