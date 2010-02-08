<?php

function app_change_handler($oak,$change)
{
	print "BeerCrush change handler:".$change->id."\n";
	
	$parts=preg_split('/:/',$change->id);
	switch ($parts[0])
	{
	case 'beer':
		$oak->purge_document_cache('web','/api/'.str_replace(':','/',$change->id));
		break;
	case 'review':
		$beer_id=$parts[1].':'.$parts[2].':'.$parts[3];
		// Purge the view doc
		$oak->purge_view_cache('beer_reviews/for_beer?key=%22'.$beer_id.'%22');
		// Purge the beer doc
		$oak->purge_document_cache('couchdb',$beer_id);
		// Force a recalc and re-cache of the review info by re-requesting the beer doc
		$oak->simple_http_request($oak->get_config_info()->api->base_uri.'/'.str_replace(':','/',$beer_id));
		break;
	}
}

?>