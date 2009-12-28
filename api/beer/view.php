<?php
// header("Cache-Control: no-cache");
require_once('beercrush/oak.class.php');
// header('Content-Type: text/plain');
// print_r($_SERVER);exit;
if (empty($_GET['beer_id']))
{
	header('HTTP/1.0 404 No beer_id');
	print "No beer_id";
	exit;
}

function tally_sum($row,$name,&$averages)
{
	if (isset($row->value->$name))
	{
		$averages[$name]+=$row->value->$name;
		$averages["{$name}_count"]++;
	}
	
}

$oak=new OAK;
$beerdoc=new OAKDocument('');
$oak->get_document($_GET['beer_id'],$beerdoc);
$beerdoc->id=$beerdoc->_id;
unset($beerdoc->_id);
unset($beerdoc->_rev);

/*
	Compute review_summary data
*/
$reviews=new stdClass;
$oak->get_view('beer_reviews/for_beer?key=%22'.$_GET['beer_id'].'%22',&$reviews);

$averages=array();
$flavors=array();

foreach ($reviews->rows as $row)
{
	// rating, srm, body, balance & aftertaste are all numeric values so we average them
	tally_sum($row,'rating',$averages);
	tally_sum($row,'srm',$averages);
	tally_sum($row,'body',$averages);
	tally_sum($row,'balance',$averages);
	tally_sum($row,'aftertaste',$averages);
	
	// flavors is an array of values. We want the most common ones so collect them all.
	if (!empty($row->value->flavors))
	{
		foreach ($row->value->flavors as $flavor)
		{
			if (!isset($flavors[$flavor]))
				$flavors[$flavor]=0;
			$flavors[$flavor]++;
		}
	}
}

$beerdoc->review_summary=new stdClass;
$beerdoc->review_summary->total=$averages['rating_count'];
if ($averages['rating_count'])
	$beerdoc->review_summary->avg=$averages['rating'] / $averages['rating_count'];
if ($averages['srm_count'])
	$beerdoc->review_summary->srm_avg=$averages['srm'] / $averages['srm_count'];
if ($averages['body_count'])
	$beerdoc->review_summary->body_avg=$averages['body'] / $averages['body_count'];
if ($averages['balance_count'])
	$beerdoc->review_summary->balance_avg=$averages['balance'] / $averages['balance_count'];
if ($averages['aftertaste_count'])
	$beerdoc->review_summary->aftertaste_avg=$averages['aftertaste'] / $averages['aftertaste_count'];

// Get the most-common flavors
if (arsort($flavors)===true)
	$beerdoc->review_summary->flavors=array_slice(array_keys($flavors),0,5);

// Add the thumbnail photo info
$photoset=new OAKDocument('');
$oak->get_document('photoset:'.$_GET['beer_id'],$photoset);
// print_r($photoset);exit;
$beerdoc->photos=new stdClass;
$beerdoc->photos->total=count($photoset->photos);
if ($beerdoc->photos->total)
{
	$idx=0; // Take the 1st one if we don't have one specified
	if (isset($photoset->default_photo_index))
		$idx=$photoset->default_photo_index;
	$beerdoc->photos->thumbnail=$photoset->photos[$idx]->thumbnail->url;
}

header('X-CacheKey: foobar');
header('Content-Type: application/json; charset=utf-8');
print json_encode($beerdoc);

?>