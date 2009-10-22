<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$beers=new stdClass;
$oak->get_view('beer/all',$beers);
// var_dump($beers);exit;

$doc=array();
foreach ($beers->rows as $row)
{
	$letter=strtoupper(substr($row->key,0,1));
	if (!ctype_alpha($letter))
		$letter='#';

	$parts=preg_split('/:/',$row->id);
	
	$doc[$letter][]=array(
		"id" => $row->id,
		"name" => $row->key,
		"brewery_id" => 'brewery:'.$parts[1],
	);
}

print json_encode($doc);

?>