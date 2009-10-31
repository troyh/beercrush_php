<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$viewdoc=new stdClass;
$viewurl='beer/made_by?key=%22'.$_GET['brewery_id'].'%22';
$oak->get_view($viewurl,$viewdoc);

$beerlist=array(
	'beers' => array(),
);

foreach ($viewdoc->rows as $row)
{
	$beerdoc=new OAKDocument('');
	$oak->get_document($row->id,$beerdoc);
	$beerlist['beers'][]=array(
		'beer_id' => $beerdoc->getID(),
		'name' => $beerdoc->name,
		'description' => $beerdoc->description,
	);
}

header('Content-Type: text/javascript; charset=utf-8');
print json_encode($beerlist);

?>