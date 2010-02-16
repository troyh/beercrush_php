<?php

function app_change_handler($oak,$change)
{
	print "BeerCrush change handler:".$change->id."\n";
	$oak->log("BeerCrush change handler:".$change->id);
	
	$parts=preg_split('/:/',$change->id);
	switch ($parts[0])
	{
	case 'beer':
		// Purge the beerlist view for the brewery
		$oak->purge_view_cache('beer/made_by?key=%22brewery:'.$parts[1].'%22');
		// Purge the beerlist for the brewery
		$oak->purge_document_cache('web','/api/brewery/'.$parts[1].'/beerlist');
		// Purge the brewery's page
		$oak->purge_document_cache('web','/brewery/'.$parts[1]);
		// Fall through....
	case 'brewery':
	case 'place':
		// Purge the beer/brewery/place's API doc 
		$oak->purge_document_cache('web','/api/'.str_replace(':','/',$change->id));
		// Purge the beer/brewery/place's page
		$oak->purge_document_cache('web','/'.str_replace(':','/',$change->id));
		break;
	case 'review':
		$beer_id=$parts[1].':'.$parts[2].':'.$parts[3];
		// Purge the view doc
		$oak->purge_view_cache('beer_reviews/for_beer?key=%22'.$beer_id.'%22');
		// Purge the beer doc
		$oak->purge_document_cache('couchdb',$beer_id);
		// Force a recalc and re-cache of the review info by re-requesting the beer doc
		file_get_contents($oak->get_config_info()->api->base_uri.'/'.str_replace(':','/',$beer_id));
		break;
	case 'user':
		// Purge the user's API doc 
		$oak->purge_document_cache('web','/api/'.str_replace(':','/',$change->id));
		// Purge the user's page
		$oak->purge_document_cache('web','/'.str_replace(':','/',$change->id));
		break;
	}
}

?>