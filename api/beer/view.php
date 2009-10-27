<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$beerdoc=new OAKDocument('');
$oak->get_document($_GET['beer_id'],$beerdoc);
unset($beerdoc->_id);
unset($beerdoc->_rev);
header('Content-Type: text/javascript; charset=utf-8');
print json_encode($beerdoc);

?>