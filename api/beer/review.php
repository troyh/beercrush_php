<?php
require_once("beercrush/oak.class.php");
require_once('beercrush/Beer.class.php');

$cgi_fields=array(
	"beer_id"		   => array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_TEXT, validatefunc=>'Beer::validateID' ),
	"rating"		   => array(flags=>OAK::FIELDFLAG_REQUIRED, type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"srm"			   => array(type=>OAK::DATATYPE_INT, min=>0, max=>9),
	"body"			   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"balance"	   	   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"aftertaste"	   => array(type=>OAK::DATATYPE_INT, min=>0, max=>5),
	"comments"		   => array(type=>OAK::DATATYPE_TEXT),
	"flavors"		   => array(type=>OAK::DATATYPE_TEXT,flags=>OAK::FIELDFLAG_CGIONLY),
	"purchase_price"   => array(type=>OAK::DATATYPE_MONEY),
	"purchase_place_id"=> array(type=>OAK::DATATYPE_TEXT),
	"poured_from"	   => array(type=>OAK::DATATYPE_TEXT),
	"date_drank"	   => array(type=>OAK::DATATYPE_TEXT),
	// "size"			   => array(type=>OAK::DATATYPE_TEXT),
	// "drankwithfood"	   => array(type=>OAK::DATATYPE_TEXT),
	// "food_recommended" => array(type=>OAK::DATATYPE_BOOL),
);

require_once('beercrush/BeerReview.php');

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

			$oak->log('Loading flavors data from XML document');

			// Read Flavors XML doc
			$xml=simplexml_load_file($oak->get_file_location('XML_DIR').'/flavors.xml');
			if ($xml===false)
				$oak->log('Unable to parse XML doc:'.$oak->get_file_location('XML_DIR').'/flavors.xml',OAK::LOGPRI_CRIT);
			else
			{
				$flavors=$xml->xpath('/flavors/group/flavors/flavor');
				foreach ($flavors as $flavor)
				{
					$flavorslist[(string)$flavor['id']]=(string)$flavor;
				}
				
				// Store list in shared mem
				if (shm_put_var($shm_id,'1',$flavorslist)===false)
				{
					// TODO: what to do? We don't want to keep parsing the XML doc if this keeps failing.
					$oak->log('Failed to store flavors data in shared memory',OAK::LOGPRI_CRIT);
				}
			}
		}

		shm_detach($shm_id);
	}
}

function oakMain($oak)
{
	header("Cache-Control: no-cache");
	
	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		$oak->request_login();
	}
	else
	{
		global $cgi_fields;
		
		$beer_id=$oak->get_cgi_value('beer_id',$cgi_fields);
		// Validate the beer_id
		$beer=new OAKDocument('');
		if ($oak->get_document($beer_id,&$beer)!==true)
			throw new Exception('Invalid beer_id:'.$beer_id);

		$review=BeerReview::createReview($beer_id,$oak->get_user_id());

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
			if ($updating_review)
			{
				// Uncache the data in the web proxies for the beer
				$oak->purge_document_cache('app','/api/review/beer?user_id='.$oak->get_user_id().'&beer_id='.$beer_id);
				$oak->purge_document_cache('app','/api/review/beer?beer_id='.$beer_id.'&user_id='.$oak->get_user_id());
				// NOTE: we have to do both versions because we never know how the URL was requested and then cached
			}
			
			header("Content-Type: application/json; charset=utf-8");
			print json_encode($review);
		}
	}
}

require_once('beercrush/oak.php');

?>