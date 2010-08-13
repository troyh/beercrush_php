<?php
header("Cache-Control: no-cache");
require_once('beercrush/beercrush.php');

$cgi_fields=array();

function oakMain($oak) {
	if (empty($_GET['id']))
		throw new Exception('Missing id');
	
	// Retrieve doc
	$doc=new OAKDocument('');
	if ($oak->get_document($_GET['id'],&$doc)!==true) {
		// Don't care, we'll create a new doc
	}

	$parts=preg_split('/:/',$_GET['id']);
	$doc->type=$parts[0];

	$myPOST=getRealPOST();
	if (!empty($myPOST)) {
		$modified=false;
		// Assign values	
		foreach ($myPOST as $k=>$v) {
			if (assign_json_object($doc,$k,$v)) {
				$modified=true;
			}
		}

		if ($modified) {
			// Save doc
			if ($oak->put_document($_GET['id'],$doc)===false)
				throw new Exception('Unable to save document');
		}
	}

	header("Content-Type: application/json; charset=utf-8;");
	print json_encode($doc)."\n";
}

require_once('OAK/oak.php');

function assign_json_object($doc,$lhs,$rhs) {
	$modified=false;
	
	$ref=&$doc;
	$components=preg_split('/\\./',$lhs);
	
	if (!count($components))
		return null;

	// Convert value if appropriate
	if (is_numeric($rhs)) {
		$rhs=(float)$rhs; // Assume float
		if (!is_float($rhs)) // Convert to int if appropriate
			$rhs=(int)$rhs;
	}
	else if ($rhs==='true') 
		$rhs=true;
	else if ($rhs==='false')
		$rhs=false;

	if (is_string($rhs) && !strlen($rhs)) {
		for ($i=0,$j=count($components)-1; $i<$j; ++$i) {
			if (!isset($ref->{$component})) {
				$ref->{$component}=new stdClass;
			}
			$ref=&$ref->{$component};
		}

		if (isset($ref->{$components[count($components)-1]})) {
			unset($ref->{$components[count($components)-1]}); // Unset the last one
			$modified=true;
		}
	}
	else {
		// Find/create path variable(s)
		foreach ($components as $component) {
			if (!isset($ref->{$component})) {
				$ref->{$component}=new stdClass;
			}
			$ref=&$ref->{$component};
		}

		if ($ref!=$rhs) {
			// Assign the value
			$ref=$rhs;
			$modified=true;
		}
	}
	return $modified;
}

function getRealPOST() {
    $pairs = preg_split('/&/', file_get_contents("php://input"),-1,PREG_SPLIT_NO_EMPTY);
    $vars = array();
    foreach ($pairs as $pair) {
        $nv = explode("=", $pair);
        $name = urldecode($nv[0]);
        $value = urldecode($nv[1]);
        $vars[$name] = $value;
    }
    return $vars;
}
?>