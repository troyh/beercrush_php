<?php
require_once('beercrush/beercrush.php');

$level=1;
$startkeys=array();
if (!empty($_GET['country'])) {
	$startkeys[]=$_GET['country'];
	$level=2;
	if (!empty($_GET['state'])) {
		$level=3;
		$startkeys[]=$_GET['state'];
		if (!empty($_GET['city'])) {
			$level=4;
			$startkeys[]=$_GET['city'];
		}
	}
}

$url='location/'.$_GET['type'].'?group_level='.$level;
if (count($startkeys)) {
	$url.='&startkey='.urlencode(json_encode($startkeys));
	$startkeys[count($startkeys)-1].='\uFFFF'; // We have to add this because endkey doesn't include the last item
	$url.='&endkey='.urlencode(json_encode($startkeys));
}

$view=new stdClass;
$BC->oak->get_view($url,&$view);

$locations=array();
foreach ($view->rows as $row) {
	if (count($row->key) == 3)
		$locations[$row->key[0]][$row->key[1]][$row->key[2]]+=$row->value;
	else if (count($row->key) == 2)
		$locations[$row->key[0]][$row->key[1]]+=$row->value;
	else if (count($row->key) == 1)
		$locations[$row->key[0]]+=$row->value;
}

header('Content-Type: application/json; charset=utf-8');
print json_encode($locations);

?>