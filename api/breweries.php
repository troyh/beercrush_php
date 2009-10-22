<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$breweries=new stdClass;
$oak->get_view('brewery/all',$breweries);
// var_dump($breweries);exit;

$doc=array();
foreach ($breweries->rows as $row)
{
	$letter=strtoupper(substr($row->key,0,1));
	if (!ctype_alpha($letter))
		$letter='#';
	$doc[$letter][]=array(
		"id" => $row->id,
		"name" => $row->key,
	);
}

print json_encode($doc);

?>