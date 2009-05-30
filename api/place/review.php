<?php
require_once("beercrush/oak_defines.php");

$cgi_fields=array(
	"place_id"		=> array(flags=>OAK_FIELDFLAG_REQUIRED, type=>OAK_DATATYPE_TEXT,  	min=>0, max=>-1, userfunc=>validatePlaceID ),
	"rating"		=> array(flags=>OAK_FIELDFLAG_REQUIRED, type=>OAK_DATATYPE_INT, 	min=>0, max=>5,  userfunc=>null ),
);
$conf_file="/etc/BeerCrush/json.conf";
$cgi_flags=0;

require_once('beercrush/PlaceReview.php');
require_once('beercrush/oak.php');


function oakMain($oak)
{
	$review=new PlaceReview;

	// Give it enough info so that an ID can be created
	$review->user_id=$oak->get_user_id();
	$review->place_id=$oak->get_cgi_value('place_id');

	// Get existing review, if there is one so that we can update just the parts added/changed in this request
	if ($oak->get_document($review->getID(),&$review)===true)
	{
		// Do nothing, it doesn't matter
	}

	// Give it this request's edits
	$oak->assign_values(&$review);
	
	// Store in db
	$oak->put_document($review);
}

?>