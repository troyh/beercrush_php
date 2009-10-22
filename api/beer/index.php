<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$beerdoc=new OAKDocument('');
$oak->get_document('beer:'.str_replace('/',':',$_GET['id']),$beerdoc);

print json_encode($beerdoc);

?>