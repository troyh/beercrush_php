<?php
require_once('OAK/oak.class.php');
$oak=new OAK;
$doc=new stdClass;
$oak->get_document('flavors',&$doc);

function flatlist($flavors) {
	$flatlist=array();
	foreach ($flavors as $flavor) {
		if (!empty($flavor->id)) {
			$flatlist[$flavor->id]=$flavor;
		}
		if (isset($flavor->flavors)) {
			$flatlist=array_merge($flatlist,flatlist($flavor->flavors));
		}
	}
	return $flatlist;
}

header('Content-Type: application/json; charset=utf-8');
print json_encode(flatlist($doc->flavors));

?>