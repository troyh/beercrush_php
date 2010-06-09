<?php
require_once('beercrush/beercrush.php');

if (empty($_GET['country'])) {
	$id_parts=array();
	$view_url='place/locations?group_level=1';
}
else {
	$id_parts=array($_GET['country']);

	if (!empty($_GET['state']))
		$id_parts[]=$_GET['state'];
	
	if (!empty($_GET['city']))
		$id_parts[]=$_GET['city'];

	$skey=json_encode($id_parts);
	// Make a copy...
	$id_parts2=$id_parts;
	$id_parts2[count($id_parts)-1].='\\uFFFF';
	$ekey=json_encode($id_parts2);
	$view_url='place/locations?group_level=3&inclusive_end=true&startkey='.urlencode($skey).'&endkey='.urlencode($ekey);
}

$locations=new stdClass;
if ($BC->oak->get_view($view_url,&$locations)===false)
	throw new Exception('Unable to get locations document');

$id='location:'.join(':',$id_parts);

if (count($locations->rows) == 1) {

	$hierarchy=array();
	for ($i=0,$j=count($locations->rows[0]->key)-1; $i < $j; ++$i) {
		$hierarchy[]='location:'.join(':',array_slice($locations->rows[0]->key,0,$i+1));
	}

	list($name)=array_slice($locations->rows[0]->key,-1,1);
	$answer=array(
		'id' => $id,
		'type' => 'location',
		'name' => $name,
		'stats' => array(
			'places' => $locations->rows[0]->value,
		),
		'hierarchy' => $hierarchy,
	);
}
else {
	$hierarchy=array();
	for ($i=0,$j=count($id_parts)-1;$i < $j;++$i)
		$hierarchy[]='location:'.$id_parts[$i];
		
	$sublocations=array();
	// print_r($locations->rows);
	$total_places=0;
	foreach ($locations->rows as $row) {
		list($name)=array_slice($row->key,count($id_parts),1);
		if (isset($sublocations[$name])) {
			$sublocations[$name]['stats']['places']+=$row->value;
			$total_places+=$row->value;
		}
		else {
			$sublocations[$name]=array(
				'id' => 'location:'.join(':',array_slice($row->key,0,count($id_parts)+1)),
				'name' => $name,
				'stats' => array(
					'places' => $row->value,
				),
			);

			$total_places+=$row->value;
		}
	}
	
	$answer=array(
		'id' => $id,
		'type' => 'location',
		'name' => join(' ',array_reverse($id_parts)),
		'hierarchy' => $hierarchy,
		'locations' => array_values($sublocations),
		'stats' => array(
			'places' => $total_places
		),
	);
}

header('Content-Type: application/json; charset=utf-8');
print json_encode($answer);

?>