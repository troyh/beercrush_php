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

<?php
$answer=BeerCrush::api_doc($BC->oak,'location/breweries');
// print_r($countries);exit;
foreach ($answer as $country=>$total) {
	$country_total++;
	$brewery_total+=$total;
}
?>
<div>count of total number of breweries:<?=$brewery_total?></div>
<div>number of countries they are in:<?=$country_total?></div>

<h1>Brewer of the Day</h1>

<?php
$answer=solr_query(array(
	'q' => 'doctype:brewery',
	'sort' => 'random_'.date('Ymd').' asc',
	'rows' => 1,
));
$botd=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($answer->response->docs[0]->id));
// TODO: Index the number of rated beers for each brewery so we can search by it
// TODO: Only pick a brewery that has 5 rated beers

// Get their top-rated beers
$brewery_id=explode(':',BeerCrush::beer_id_to_brewery_id($botd->id));
$beers=solr_query(array(
	'q' => 'id:'.'beer\\:'.$brewery_id[1].'\\:*',
	'sort' => 'rating desc',
	'rows' => 10
));
?>

<div>
	<?=BeerCrush::brewery_html($botd)?>
</div>

<h2>Their top-rated beers</h2>

<ul>
	<?php foreach ($beers->response->docs as $doc):
		$beer=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
	?>
		<li><?=BeerCrush::beer_html_mini($beer)?></li>
	<?php endforeach; ?>
</ul>

<h1>Breweries Nearby</h1>

NYI <!-- TODO: Show nearby breweries -->

<h1>5 New Breweries</h1>

<?php
$breweries=solr_query(array(
	'q' => 'doctype:brewery',
	'sort' => 'ctime desc',
	'rows' => 5,
));
?>
<ul>
<?php foreach ($breweries->response->docs as $doc):
	$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
?>
	<li>
		<?=BeerCrush::brewery_html($brewery)?>
	</li>
<?php endforeach;?>
</ul>

<?php
include('../footer.php');
?>
