<?php
namespace BeerCrush\Metadata\Brewery {

function beersreviewed($oak,$doc) {
	$metadata=new \OAKDocument();
	if ($oak->get_document('metadata:'.$doc->id,&$metadata)===false) {
		return 0;
	}

	return $metadata->beers->reviewed;
}

}

?>