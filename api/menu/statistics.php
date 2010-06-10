<?php
require_once('beercrush/beercrush.php');

$view=new stdClass;
$BC->oak->get_view('menu/counts?group_level=1',&$view);

$answer=array(
	'total_menus' => $view->rows[0]->value,
	'total_beers' => $view->rows[1]->value,
);

header('Content-Type: application/json; charset=utf-8');
print json_encode($answer);

?>