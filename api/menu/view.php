<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$menu=new stdClass;
if ($oak->get_document('menu:'.$_GET['place_id'],$menu)!==true)
{
	header('HTTP/1.0 400 No menu');
	exit;
}
else
{
	unset($menu->_id);
	unset($menu->_rev);
	header('Content-Type: text/javascript; charset=utf-8');
	print json_encode($menu);
}

?>