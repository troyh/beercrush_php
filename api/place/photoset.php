<?php
header("Cache-Control: no-cache");
require_once('beercrush/oak.class.php');

if (empty($_GET['place_id']))
{
	header('HTTP/1.0 404 No place_id');
	print "No place_id";
	exit;
}

$oak=new OAK;
$photoset=new OAKDocument('');
if ($oak->get_document('photoset:'.$_GET['place_id'],$photoset)===false)
{
	$photoset->id='photoset:'.$_GET['place_id'];
	$photoset->photos=array();
}
else
{
	$photoset->id=$photoset->_id;
	unset($photoset->_id);
	unset($photoset->_rev);
}

$photoset->id=$photoset->_id;
unset($photoset->_id);
unset($photoset->_rev);

header('Content-Type: application/json; charset=utf-8');
print json_encode($photoset);

?>
