<?php
require_once('OAK/oak.class.php');
$oak=new OAK;
$doc=new stdClass;
$oak->get_document('flavors',&$doc);
header('Content-Type: application/json; charset=utf-8');
unset($doc->_id);
unset($doc->_rev);
print json_encode($doc);
?>