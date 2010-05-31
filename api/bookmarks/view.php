<?php
require_once('beercrush/beercrush.php');

header('Content-Type: application/json; charset=utf-8');

$bookmarks=new OAKDocument('bookmarks');
$docid='bookmarks:'.$BC->oak->get_user_id();
if ($BC->oak->get_document($docid,&$bookmarks)===false) {
	// header('HTTP/1.0 404 No bookmarks');
	print "{}\n";
	exit;
}

// Add data for bookmarks
foreach ($bookmarks->items as $id=>&$info) {
	$doc=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($id));
	$info->name=$doc->name;
}

unset($bookmarks->_id);
unset($bookmarks->_rev);

print json_encode($bookmarks);

?>
