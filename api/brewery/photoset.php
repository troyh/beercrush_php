<?php
header("Cache-Control: no-cache");
require_once('OAK/oak.class.php');

if (empty($_GET['brewery_id']))
{
	header('HTTP/1.0 404 No brewery_id');
	print "No brewery_id";
	exit;
}

$oak=new OAK;
$photoset=new OAKDocument('photoset');
if ($oak->get_document('photoset:'.$_GET['brewery_id'],&$photoset)===false)
{
	$photoset->id='photoset:'.$_GET['brewery_id'];
	$photoset->photos=array();
}
else
{
	$photoset->id=$photoset->_id;
	unset($photoset->_id);
	unset($photoset->_rev);
}

header('Content-Type: application/json; charset=utf-8');
print json_encode($photoset);

?>
