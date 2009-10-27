<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$brewerydoc=new OAKDocument('');
$oak->get_document($_GET['brewery_id'],$brewerydoc);
unset($brewerydoc->_id);
unset($brewerydoc->_rev);
header('Content-Type: text/javascript; charset=utf-8');
print json_encode($brewerydoc);

?>