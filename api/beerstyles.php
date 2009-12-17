<?php
require_once('beercrush/oak.class.php');
$oak=new OAK;
$doc=new stdClass;
$oak->get_document('beerstyles',$doc);
unset($doc->_id);
unset($doc->_rev);
header('Content-Type: application/json; charset=utf-8');
print json_encode($doc);

?>