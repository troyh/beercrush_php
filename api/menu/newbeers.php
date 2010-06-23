<?php
require_once('beercrush/beercrush.php');

$filename=$BC->oak->get_config_info()->file_locations->LOCAL_DIR.'/menus/newbeers/'.$_GET['country'].'/'.$_GET['state'].'/'.$_GET['city'].'.json';
$newbeers=json_decode(file_get_contents($filename));

foreach ($newbeers as &$menu) {
	
	$place_id=str_replace('menu:','',$menu->menu_id);
	$place=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($place_id));
	
	$menu->place=array(
		'id' => $place->id,
		'placetype' => $place->placetype,
		'name' => $place->name,
		'review_summary' => $place->review_summary,
		'photos' => $place->photos,
	);
	
	foreach ($menu->beers as &$beer_id) {
		
		$doc=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($beer_id));
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->brewery_id));

		$beer_id=array(
			'id' => $doc->id,
			'name' => $doc->name,
			'brewery' => array(
				'id' => $brewery->id,
				'name' => $brewery->name,
			),
			'review_summary' => $doc->review_summary,
			'photos' => $doc->photos,
		);
	}
}

header("Content-Type: application/json;charset=utf-8");
print json_encode($newbeers);

?>