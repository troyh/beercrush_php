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

$header['js'][]='<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';

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
Your Location: <span id="location_text"></span>
<a href="" onclick="$('#setlocation_form').toggleClass('hidden');return false;">Change my location</a>
<div id="setlocation_form" class="hidden">
	<input id="location_box" type="text" size="10" value="" /><input type="button" onclick="BeerCrush.geolocate_user($('#location_box').val(),show_nearby_breweries);" value="Go" />
	<a href="" onclick="BeerCrush.geolocate_user(null,show_nearby_breweries);return false;">Ask my browser</a>
</div>

<div id="nolocation_msg" class="hidden">
	<h2>Your location is not known.</h2>
	<a href="" onclick="$('#setlocation_form').removeClass('hidden');return false;">Locate me</a>
</div>

<ul id="nearby_breweries"></ul>

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
<script type="text/javascript">

function show_nearby_breweries() {
	$('#nearby_breweries').empty();
	var latlon=BeerCrush.get_user_location();
	if (latlon) {
		BeerCrush.geocode_location(latlon.lat,latlon.lon, function(s) {
			$('#location_text').html(s);
		});
		$.getJSON('/api/nearby.fcgi',{
			lat: latlon.lat,
			lon: latlon.lon,
			types: 16
		},function(data) {
			$.each(data.places,function(i,item){
				$('#nearby_breweries').append('<li><a href="/'+item.id.replace(/:/,'/')+'">'+item.name+'</a></li>');
			});
		});
	}
}

function pageMain() {
	// Ajax in the nearby breweries
	show_nearby_breweries();

}
</script>
<?php
include('../footer.php');
?>
