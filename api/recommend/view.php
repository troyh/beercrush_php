<?php
// header("Cache-Control: no-cache");
require_once 'beercrush/beercrush.php';

if (empty($_GET['id'])) {
	exit;
}

$oak=new OAK(BeerCrush::CONF_FILE);
if ($oak->get_document("recommend:".$_GET['id'],&$doc)===false) {
	header("HTTP/1.0 404 Not found");
	exit;
}

header('Content-Type: application/json; charset=utf-8');
print json_encode($doc);

?>