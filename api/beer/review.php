<?php
require_once("beercrush/oak.class.php");
require_once('beercrush/Beer.class.php');

$cgi_fields=array(
	"beer_id"		   => array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_TEXT, validatefunc=>'Beer::validateID' ),
	"rating"		   => array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"srm"			   => array(type=>OAK::DATATYPE_INT, min=>0, max=>9),
	"body"			   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"bitterness"	   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"sweetness"		   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"aftertaste"	   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"comments"		   => array(type=>OAK::DATATYPE_TEXT),
	"price"			   => array(type=>OAK::DATATYPE_MONEY),
	"place"			   => array(type=>OAK::DATATYPE_TEXT),
	"size"			   => array(type=>OAK::DATATYPE_TEXT),
	"drankwithfood"	   => array(type=>OAK::DATATYPE_TEXT),
	"food_recommended" => array(type=>OAK::DATATYPE_BOOL),
);

require_once('beercrush/BeerReview.php');


function oakMain($oak)
{
	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		header("HTTP/1.0 401 Login required");
	}
	else
	{
		$review=BeerReview::createReview($oak->get_cgi_value('beer_id'),$oak->get_user_id());

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