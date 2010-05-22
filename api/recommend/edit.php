<?php
header("Cache-Control: no-cache");
require_once 'beercrush/beercrush.php';

// TODO: only allow admins to call this

if (empty($_GET['id'])) {
	header("HTTP/1.0 403 Missing id");
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("HTTP/1.0 403 Must be POST");
	exit;
}

$oak=new OAK(BeerCrush::CONF_FILE);

$oak->get_document($_GET['id'],&$doc);

$doc->beer=array();
$doc->place=array();
$doc->brewery=array();
$doc->style=array();

$recommended=explode("\n",$_POST['recommended']);
foreach ($recommended as $r) {
	if (!empty($r)) {
		$parts=explode(':',$r);
		$doc->{$parts[0]}[]=$r;
	}
}

if ($oak->put_document($_GET['id'],$doc)===false) {
	header("HTTP/1.0 403 Edit failed");
	exit;
}

print json_encode($doc);

?>