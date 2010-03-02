<?php
require_once('OAK/oak.class.php');

$oak=new OAK;
$placedoc=new OAKDocument('');
$oak->get_document($_GET['place_id'],$placedoc);
$placedoc->id=$placedoc->_id;
unset($placedoc->_id);
unset($placedoc->_rev);
unset($placedoc->{"@attributes"});
header('Content-Type: application/json; charset=utf-8');
print json_encode($placedoc);

?>