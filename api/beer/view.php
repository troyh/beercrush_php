<?php
require_once('beercrush/oak.class.php');
// header('Content-Type: text/plain');
// print_r($_SERVER);exit;
if (empty($_GET['beer_id']))
{
	header('HTTP/1.0 404 No beer_id');
	print "No beer_id";
	exit;
}

$oak=new OAK;
$beerdoc=new OAKDocument('');
$oak->get_document($_GET['beer_id'],$beerdoc);
$beerdoc->id=$beerdoc->_id;
unset($beerdoc->_id);
unset($beerdoc->_rev);

$reviews=new stdClass;
$oak->get_view('beer_reviews/for_beer?key=%22'.$_GET['beer_id'].'%22',&$reviews);

$total_rating=0;
foreach ($reviews->rows as $row)
{
	$total_rating+=$row->value->rating;
}

$beerdoc->rating_count=count($reviews->rows);
if ($beerdoc->rating_count)
	$beerdoc->rating_avg=$total_rating / $beerdoc->rating_count;

header('Content-Type: application/json; charset=utf-8');
print json_encode($beerdoc);

?>