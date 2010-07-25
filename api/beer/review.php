<?php
header("Cache-Control: no-cache");
require_once 'OAK/oak.class.php';

$cgi_fields=array(
	"beer_id"		   => array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_TEXT, validatefunc=>'Beer::validateID' ),
	"rating"		   => array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"srm"			   => array(type=>OAK::DATATYPE_INT, min=>0, max=>9),
	"body"			   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"balance"	   	   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"aftertaste"	   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"comments"		   => array(type=>OAK::DATATYPE_TEXT),
	"flavors"		   => array(type=>OAK::DATATYPE_TEXT,flags=>OAK::FIELDFLAG_CGIONLY),
	"purchase_price"   => array(type=>OAK::DATATYPE_MONEY,minlen=>0),
	"purchase_place_id"=> array(type=>OAK::DATATYPE_TEXT),
	"poured_from"	   => array(type=>OAK::DATATYPE_TEXT),
	"date_drank"	   => array(type=>OAK::DATATYPE_TEXT),
	// "size"			   => array(type=>OAK::DATATYPE_TEXT),
	// "drankwithfood"	   => array(type=>OAK::DATATYPE_TEXT),
	// "food_recommended" => array(type=>OAK::DATATYPE_BOOL),
);

function recurse_flavors($flavors,&$flavorslist)
{
	foreach ($flavors as $flavor)
	{
		if  (!empty($flavor->id))
			$flavorslist[$flavor->id]=$flavor;
		if (!empty($flavor->flavors))
			recurse_flavors($flavor->flavors,$flavorslist);
	}
}

function load_flavors_data(&$flavorslist, $oak)
{
	$flavorslist=array(); // If everything fails, they get an empty array back
	
	// If the flavors info is not in shared memory...
	$shm_key=ftok(__FILE__,"B");
	$shm_id=shm_attach($shm_key);
	if ($shm_id)
	{
		$flavorslist=@shm_get_var($shm_id,'1');
		if ($flavorslist===false) // Damn, we have to read the XML doc and put it in shared memory
		{
			$flavorslist=array(); // Make sure they get an empty array back, if the rest fails

			$oak->log('Loading flavors data');

			// Read Flavors JSON doc
			$flavors_data=json_decode(file_get_contents('http://'.$oak->get_config_info()->domainname.'/api/flavors'));
			if (is_null($flavors_data))
				$oak->log('Unable to parse flavors JSON doc http://'.$oak->get_config_info()->domainname.'/api/flavors',OAK::LOGPRI_CRIT);
			else
			{
				recurse_flavors($flavors_data->flavors,$flavorslist);
				
				// Store list in shared mem
				if (shm_put_var($shm_id,'1',$flavorslist)===false)
				{
					$oak->log('Failed to store flavors data in shared memory',OAK::LOGPRI_CRIT);
				}
			}
		}

		shm_detach($shm_id);
	}
}

function oakMain($oak)
{
	header("Cache-Control: no-cache"); // This page should never be cached, it's an API call
	
	global $cgi_fields;
	
	$user_id=$oak->get_user_id();
	$beer_id=$oak->get_cgi_value('beer_id',$cgi_fields);

	if (empty($beer_id))
		throw new Exception('beer_id is empty');
	if (empty($user_id))
		throw new Exception('user_id is empty');

	// Validate the beer_id
	$beer=new OAKDocument('');
	if ($oak->get_document($beer_id,&$beer)!==true)
		throw new Exception('Invalid beer_id:'.$beer_id);

	// Verify that user_id is a valid ID for existing user
	$user=new OAKDocument('');
	if ($oak->get_document('user:'.$user_id,&$user)!==true)
		throw new Exception('Invalid user_id:'.$user_id);

	$review=new OAKDocument('review');
	$review->beer_id=$beer_id;
	$review->user_id=$user_id;
	$review->setID('review:'.$review->beer_id.':'.$review->user_id);

	// Get existing review, if there is one so that we can update just the parts added/changed in this request
	$updating_review=false;
	if ($oak->get_document($review->getID(),&$review)===true)
	{
		// Do nothing, it doesn't matter
		$oak->log('Updating review:'.$review->getID());
		$updating_review=true;
	}
	else
		$oak->log('New review:'.$review->getID());

	// Give it this request's edits
	$oak->assign_cgi_values(&$review,$cgi_fields);
	
	// Add flavors
	if ($oak->cgi_value_exists('flavors',$cgi_fields))
	{
		// Separate them into an array and remove duplicates
		$flavors=array_unique(preg_split('/[^a-zA-Z0-9]+/',$oak->get_cgi_value('flavors',$cgi_fields)));
		
		if (count($flavors))
		{
			$flavorslist=array();
			load_flavors_data(&$flavorslist, $oak);

			// Always remove all existing flavors if this is an edit of an existing review
			$review->flavors=array();
				
			foreach ($flavors as $flavor)
			{
				// Make sure each flavor is valid
				if (!empty($flavorslist[$flavor]))
				{
					$review->flavors[]=$flavor;
				}
			}
		}
	}

	// Store in db
	if ($oak->put_document($review->getID(),$review)!==true)
	{
		header("HTTP/1.0 500 Save failed");
	}
	else
	{
		$oak->broadcast_msg('newreviews',$review);
		
		header("Content-Type: application/json; charset=utf-8");
		$review->id=$review->_id;
		unset($review->_id);
		unset($review->_rev);
		print json_encode($review);
	}

}

require_once('OAK/oak.php');

?>