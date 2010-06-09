<?php
require_once('beercrush/beercrush.php');

$styles=new stdClass;
if ($BC->oak->get_document('beerstyles',$styles)===false)
	throw new Exception('Unable to get beerstyles document');

// Make styles lookup table
$lookup_table=array();
make_lookup_table($styles->styles);

header('Content-Type: application/json; charset=utf-8');
print json_encode($lookup_table);

function make_lookup_table($styles,$path=null,$id=null) {
	global $lookup_table;
	
	if (is_null($path))
		$path=array();
	if (!is_null($id))
		$path[]=$id;
		
	foreach ($styles as $style) {
		$lookup_table[$style->id]=array();
		
		foreach ($style as $k=>$v) {
			if ($k!='styles') // Exclude the sub-styles
				$lookup_table[$style->id][$k]=$v;
		}

		if (count($path))
			$lookup_table[$style->id]['hierarchy']=$path;
		
		if (!empty($style->styles)) {
			make_lookup_table($style->styles,$path,$style->id);
		}
	}
}


?>