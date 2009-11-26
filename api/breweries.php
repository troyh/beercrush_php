<?php
require_once('beercrush/oak.class.php');

header('Content-type: application/json; charset=utf-8');

mb_internal_encoding("UTF-8");

$oak=new OAK;
$breweries=new stdClass;
$oak->get_view('brewery/all',$breweries);
// var_dump($breweries);exit;

$doc=array();
foreach ($breweries->rows as $row)
{
	$c=mb_substr($row->key,0,1);
	$letter=mb_strtoupper($oak->remove_diacritics($c));
	if (!ctype_alpha($letter))
		$letter='#';
	$doc[$letter][]=array(
		"id" => $row->id,
		"name" => $row->key,
	);
}
// exit;
ksort($doc);
print json_encode($doc);

?>