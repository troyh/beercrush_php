<?php
require_once('OAK/oak.class.php');

$oak=new OAK;
$places=new stdClass;
$oak->get_view('place/all',$places);
// var_dump($places);exit;

$doc=array();
foreach ($places->rows as $row)
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