<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$brewerydoc=new OAKDocument('');
$oak->get_document($_GET['brewery_id'],$brewerydoc);
$brewerydoc->id=$brewerydoc->_id;
unset($brewerydoc->_id);
unset($brewerydoc->_rev);
unset($brewerydoc->{"@attributes"});
header('Content-Type: application/json; charset=utf-8');
print json_encode($brewerydoc);

?>