<?php
require_once('OAK/oak.class.php');
$oak=new OAK;
$doc=new stdClass;
$oak->get_document('restaurantcategories',&$doc);
header('Content-Type: text/javascript; charset=utf-8');
unset($doc->_id);
unset($doc->_rev);
print json_encode($doc);
?>