<?php
require_once("beercrush/oak.class.php");

$cgi_fields=array(
	"place_id"		=> array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_TEXT, validatefunc=>'Place::validatePlaceID' ),
	"rating"		=> array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_INT, min=>0, max=>5),
);

require_once('beercrush/PlaceReview.php');

function oakMain($oak)
{
	$review=new PlaceReview;

	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		header("HTTP/1.0 401 Login required");
	}
	else
	{
		$review=PlaceReview::createReview($oak->get_cgi_value('place_id'),$oak->get_user_id());

		// Get existing review, if there is one so that we can update just the parts added/changed in this request
		if ($oak->get_document($review->getID(),&$review)===true)
		{
			// Do nothing, it doesn't matter
		}

		// Give it this request's edits
		$oak->assign_values(&$review);
	
		// Store in db
		if ($oak->put_document($review->getID(),$review)!==true)
		{
			header("HTTP/1.0 500 Save failed");
		}
		else
		{
			$xmlwriter=new XMLWriter;
			$xmlwriter->openMemory();
			$xmlwriter->startDocument();
			
			$oak->write_document($review,$xmlwriter);
			
			$xmlwriter->endDocument();
			print $xmlwriter->outputMemory();
		}
	}

}

require_once('beercrush/oak.php');

?>