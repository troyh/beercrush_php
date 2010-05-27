<?php
require_once('OAK/oak.class.php');

if (empty($_GET['place_id'])) {
	throw new Exception('Missing place_id');
	exit;
}

$oak=new OAK;
$placedoc=new OAKDocument('');
$oak->get_document($_GET['place_id'],$placedoc);
$placedoc->id=$placedoc->_id;
unset($placedoc->_id);
unset($placedoc->_rev);
unset($placedoc->{"@attributes"});

$reviews=new stdClass;
$oak->get_view('place_reviews/by_place_id?key=%22'.$_GET['place_id'].'%22',&$reviews);

$averages=array();

foreach ($reviews->rows as $row)
{
	// rating, atmosphere, service & food are all numeric values so we average them
	tally_sum($row,'rating',$averages);
	tally_sum($row,'atmosphere',$averages);
	tally_sum($row,'service',$averages);
	tally_sum($row,'food',$averages);
}

$placedoc->review_summary=new stdClass;
$placedoc->review_summary->total=$averages['rating_count'];
if ($averages['rating_count'])
	$placedoc->review_summary->avg=$averages['rating'] / $averages['rating_count'];
if ($averages['atmosphere_count'])
	$placedoc->review_summary->atmosphere_avg=$averages['atmosphere'] / $averages['atmosphere_count'];
if ($averages['service_count'])
	$placedoc->review_summary->service_avg=$averages['service'] / $averages['service_count'];
if ($averages['food_count'])
	$placedoc->review_summary->food_avg=$averages['food'] / $averages['food_count'];


header('Content-Type: application/json; charset=utf-8');
print json_encode($placedoc);

function tally_sum($row,$name,&$averages)
{
	if (isset($row->value->$name))
	{
		$averages[$name]+=$row->value->$name;
		$averages["{$name}_count"]++;
	}
	
}

?>