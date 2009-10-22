<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$brewerydoc=new OAKDocument('');
$oak->get_document('brewery:'.str_replace('/',':',$_GET['id']),$brewerydoc);

print json_encode($brewerydoc);

?>