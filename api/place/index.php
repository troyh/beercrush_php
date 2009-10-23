<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$placedoc=new OAKDocument('');
$oak->get_document('place:'.str_replace('/',':',$_GET['id']),$placedoc);

print json_encode($placedoc);

?>