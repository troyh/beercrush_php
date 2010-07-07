<?php
header("Cache-Control: no-cache");
require_once 'beercrush/beercrush.php';

// TODO: only allow admins to call this

if (empty($_GET['id'])) {
	header("HTTP/1.0 403 Missing id");
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("HTTP/1.0 403 Must be POST");
	exit;
}

$doc=new OAKDocument();
$BC->oak->get_document($_GET['id'],&$doc);

// Make sure there are at least empty arrays for everything
$recommendation_types=array('beer','place','brewery','style','similar');
foreach ($recommendation_types as $t) {
	if (!isset($doc->$t))
		$doc->$t=array();
}

// Set recommendations
if (!empty($_POST['recommended'])) {
	$recommended=explode("\n",$_POST['recommended']);

	// Clear existing recommendations
	foreach ($recommended as $r) {
		$parts=explode(':',$r);
		if (in_array($parts[0],$recommendation_types))
			$doc->{$parts[0]}=array();
	}

	// Set all new ones
	foreach ($recommended as $r) {
		if (!empty($r)) {
			$parts=explode(':',$r);
			if (in_array($parts[0],$recommendation_types))
				$doc->{$parts[0]}[]=$r;
		}
	}
}

// Set similar beers
if (!empty($_POST['similar'])) {
	$doc->similar=array(); // Clear existing similar beers
	
	$similar=preg_split("/\s+/",$_POST['similar']);
	foreach ($similar as $s) {
		if (!empty($s)) {
			$parts=explode(':',$s);
			if ($parts[0]==="beer") // Only accept beers as similar to this beer
				$doc->similar[]=$s;
		}
	}
}

if ($BC->oak->put_document($_GET['id'],$doc)===false) {
	header("HTTP/1.0 403 Save failed");
	exit;
}

// TODO: Request purge of caches for the recommend doc. If you use this API without doing a GET first, uncache will never know about the dependency and it'll never be uncached.

header('Content-Type: application/json; charset=utf-8');
print json_encode($doc)."\n";

?>