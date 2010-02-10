<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$placedoc=new OAKDocument('');
$oak->get_document($_GET['place_id'],$placedoc);
unset($placedoc->_id);
unset($placedoc->_rev);
header('Content-Type: application/json; charset=utf-8');
print json_encode($placedoc);

?>