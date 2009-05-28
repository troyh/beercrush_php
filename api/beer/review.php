<?php
require_once("beercrush/oak_defines.php");

$cgi_fields=array(
	"beer_id"		=> array(flags=>OAK_FIELDFLAG_REQUIRED, type=>OAK_DATATYPE_TEXT,  	min=>0, max=>-1, userfunc=>validateBeerID ),
	"rating"		=> array(flags=>OAK_FIELDFLAG_REQUIRED, type=>OAK_DATATYPE_INT, 	min=>0, max=>5,  userfunc=>null ),
	"srm"			=> array(flags=>0, 						type=>OAK_DATATYPE_INT, 	min=>0, max=>9,  userfunc=>null ),
	"body"			=> array(flags=>0, 						type=>OAK_DATATYPE_INT, 	min=>0, max=>5,  userfunc=>null ),
	"bitterness"	=> array(flags=>0, 						type=>OAK_DATATYPE_INT, 	min=>0, max=>5,  userfunc=>null ),
	"sweetness"		=> array(flags=>0, 						type=>OAK_DATATYPE_INT, 	min=>0, max=>5,  userfunc=>null ),
	"aftertaste"	=> array(flags=>0, 						type=>OAK_DATATYPE_INT, 	min=>0, max=>5,  userfunc=>null ),
	"comments"		=> array(flags=>0, 						type=>OAK_DATATYPE_TEXT,  	min=>0, max=>-1, userfunc=>null ),
	"price"			=> array(flags=>0, 						type=>OAK_DATATYPE_MONEY, 	min=>0, max=>-1, userfunc=>null ),
	"place"			=> array(flags=>0, 						type=>OAK_DATATYPE_TEXT,  	min=>0, max=>-1, userfunc=>null ),
	"size"			=> array(flags=>0, 						type=>OAK_DATATYPE_TEXT,  	min=>0, max=>-1, userfunc=>null ),
	"drankwithfood"	=> array(flags=>0, 						type=>OAK_DATATYPE_TEXT,  	min=>0, max=>-1, userfunc=>null ),
	"food_recommended" => array(flags=>0, 					type=>OAK_DATATYPE_BOOL,  	min=>0, max=>-1, userfunc=>null ),
);
$conf_file="/etc/BeerCrush/json.conf";
$cgi_flags=0;

require_once('beercrush/BeerReview.php');
require_once('beercrush/oak.php');


function oakMain($oak)
{
	$review=new BeerReview;

	// Give it enough info so that an ID can be created
	$review->user_id=$oak->get_user_id();
	$review->beer_id=$oak->get_cgi_value('beer_id');

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