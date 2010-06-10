<?php
require_once('beercrush/beercrush.php');

function solr_query($params) {
	global $BC;
	$solr_cfg=$BC->oak->get_config_info()->solr;
	$args=array('wt=json');
	foreach ($params as $k=>$v) {
		$args[]=$k.'='.urlencode($v);
	}
	$url='http://'.$solr_cfg->nodes[rand()%count($solr_cfg->nodes)].$solr_cfg->url.'/select?'.join('&',$args);
	return json_decode(file_get_contents($url));
}

include('../header.php');
?>

<h1>Statistics</h1>

<div>Number of places:

<?php
$results=solr_query(array(
	'q' => 'doctype:place',
	'rows' => 0
));
?>
<?=$results->response->numFound?>
</div>

<?php
$statistics=BeerCrush::api_doc($BC->oak,'menu/statistics');
?>
<div>Number of beer menus:<?=$statistics->total_menus?></div>
<div>Number of beers on beer menus:<?=$statistics->total_beers?></div>

<h1>5 New Beer Menu Adds in Your Area</h1>

<h1>One Random Place Review</h1>

<?php
$results=solr_query(array(
	'q' => 'doctype:review AND rating:[1 TO 5] AND atmosphere:[1 TO 5] AND food:[1 TO 5] AND service:[1 TO 5] AND place_id:*',
	'sort' => 'random_'.rand().' asc',
	'rows' => 1
));
$review=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($results->response->docs[0]->id));
$place=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($review->place_id));
$reviewer=BeerCrush::api_doc($BC->oak,'user/'.$review->user_id);
?>

<div><a href="/<?=BeerCrush::docid_to_docurl($place->id)?>"><?=$place->name?></a></div>
<div>Type: <?=$place->placetype?></div>
<div>Address:
	<?=$place->address->city?>,
	<?=$place->address->state?>
	<?=$place->address->country?>
</div>

<div>User: <a href="/<?=BeerCrush::docid_to_docurl($reviewer->id)?>"><?=$reviewer->name?></a></div>
<div>Rating: <?=$review->rating?></div>
<div>Atmosphere: <?=$review->atmosphere?></div>
<div>Food: <?=$review->food?></div>
<div>Service: <?=$review->service?></div>
<div>Comments: <?=$review->comments?></div>

<h1>Top 5 Most Crushworthy Places</h1>
<h1>5 Best Cities for Brewpubs</h1>
<h1>5 Best Cities for Beer Bars</h1>
<h1>5 Best Cities for Beer Restaurants</h1>
<h1>5 Best Cities for Beer Stores</h1>

<?php
include('../footer.php');
?>
