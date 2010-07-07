<?php
header("Cache-Control: no-cache");
require_once('OAK/oak.class.php');

$cgi_fields=array(
	"add_item"			=> array(type=>OAK::DATATYPE_TEXT),
	"del_item"			=> array(type=>OAK::DATATYPE_TEXT),
);

function oakMain($oak) {
	global $cgi_fields;
	
	$adds=preg_split('/\s+/',$oak->get_cgi_value('add_item',$cgi_fields),-1,PREG_SPLIT_NO_EMPTY);
	$dels=preg_split('/\s+/',$oak->get_cgi_value('del_item',$cgi_fields),-1,PREG_SPLIT_NO_EMPTY);

	// Remove any duplicates in each list
	$adds=array_unique($adds);
	$dels=array_unique($dels);
		
	// Take the intersection of these two, these items cancel each other out
	$common=array_intersect($adds,$dels);

	// Take the difference of the intersection in each list
	$adds=array_diff($adds,$common);
	$dels=array_diff($dels,$common);
	
	if (count($adds)==0 && count($dels)==0) {
		header('HTTP/1.0 406 No-op');
		print "\n";
		exit;
	}
	
	// TODO: verify that the item being added really exists

	$docid='bookmarks:'.$oak->get_user_id();
	
	$bookmarks=new OAKDocument('bookmarks');
	if ($oak->get_document($docid,&$bookmarks)===false) {
		// Don't care
	}
	
	if (!isset($bookmarks->items))
		$bookmarks->items=new stdClass;
	
	foreach ($adds as $add) {
		if (!isset($bookmarks->items->$add)) {
			$bookmarks->items->$add=new stdClass;
		}

		$bookmarks->items->$add->ctime=time(); // Update ctime
	}
	
	foreach ($dels as $del) {
		if (isset($bookmarks->items->$del)) {
			unset($bookmarks->items->$del);
		}
	}

	header('Content-Type: application/json; charset=utf-8');
	
	if ($oak->put_document($docid,$bookmarks)===false) {
		header('HTTP/1.0 500 Save failed');
		exit;
	}
	else {
		$oak->broadcast_msg('docchanges','/api/bookmarks/'.$oak->get_user_id());

		unset($bookmarks->_id);
		unset($bookmarks->_rev);
		print json_encode($bookmarks)."\n";
	}
}

require_once('OAK/oak.php');

?>