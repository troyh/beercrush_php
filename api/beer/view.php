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
header('Content-Type: application/json; charset=utf-8');
print json_encode($beerdoc);

?>