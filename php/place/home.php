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

<div id="location_text"></div>
<a href="" onclick="$('#setlocation_form').toggleClass('hidden');return false;">Change my location</a>
<div id="setlocation_form" class="hidden">
	<input id="location_box" type="text" size="10" value="" /><input type="button" onclick="BeerCrush.geolocate_user($('#location_box').val(),show_new_beers);" value="Go" />
	<a href="" onclick="BeerCrush.geolocate_user(null,show_new_beers);return false;">Ask my browser</a>
</div>

<ul id="newbeers_list"></ul>

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

<script type="text/javascript">

function show_new_beers(id) {
	$.getJSON('/api/menu/newbeers/'+id.replace(/^location:/,'').replace(/:/g,'/'),null,function(data,textStatus){
		$('#newbeers_list').children().remove();
		$.each(data,function(idx,place) {
			$.each(place.beers,function(i,beer) {$('#newbeers_list').append('<li><a href="/'+beer.brewery.id.replace(/:/g,'/')+'">'+beer.brewery.name+'</a> <a href="/'+beer.id.replace(/:/g,'/')+'">'+beer.name+'</a> @ <a href="/'+place.place.id.replace(/:/g,'/')+'">'+place.place.name+'</a></li>');});
		});
	});
}

function shownearbylocations(lat,lon) {
	$.getJSON('/api/nearby_locations.fcgi',{
		'lat': lat,
		'lon': lon,
		'within': 10
	},function(data,textStatus){

		if ($('select#setlocation_choices').length) { // Already exists, remove option elements
			$('select#setlocation_choices').children().remove();
		}
		else { // Create it
			$('#setlocation_form').append('<select id="setlocation_choices"></select>');
			$('select#setlocation_choices').change(function(){
				if ($(this).val().length==0) 
					return;
					
				var chosen_id=$(this).val();
				// Find the option they chose...
				var chosen_name=null;
				$(this).children().each(function(idx,elem){
					if ($(elem).val()==chosen_id) {
						chosen_name=$(elem).text();
						return false;
					}
				});

				var expdate = new Date();
				expdate.setTime(expdate.getTime() + (7 * 24 * 60 * 60 * 1000)); // set to expire in 7 days
				$.cookie('location_id',chosen_id,{path: '/', expires: expdate});
				$.cookie('location_name',chosen_name,{path: '/', expires: expdate});

				// Display the location name
				$('#location_text').html(chosen_name);
				// Remove the dropdown
				$('select#setlocation_choices').remove();
				// Hide the location change form
				$('#setlocation_form').toggleClass('hidden',true);

				// Query for nearby beer menu additions
				show_new_beers(chosen_id);
			});
		}
		
		var dropdown='<option value="">Nearby Locations...</option>';
		for (var i=0;i < data.locations.length;++i) {
			dropdown+='<option value="'+data.locations[i].id+'">'+data.locations[i].name+'</option>';
		}
		$('select#setlocation_choices').append(dropdown);
	});
	
}

function geolocate(str) {
	if (typeof(str)=='string' && str.length) {

		var geocoder = new google.maps.Geocoder();
		geocoder.geocode({
			address: str
		},
		function(results,status){
			if (status==google.maps.GeocoderStatus.OK) {
				shownearbylocations(results[0].geometry.location.lat(),results[0].geometry.location.lng());
			}
		});
		
	}
	else {
		if(navigator.geolocation) {
			browserSupportFlag = true;
			navigator.geolocation.getCurrentPosition(function(position) {
				shownearbylocations(position.coords.latitude,position.coords.longitude)
			}, function() {
			});
		}
	}
	return false;
}

function pageMain() {
	var latlon=BeerCrush.get_user_location();
	if (latlon) {
		BeerCrush.geocode_location(latlon.lat,latlon.lon,function(s){
			$('#location_text').html(s);
		});
		shownearbylocations(latlon.lat,latlon.lon);
		$.getJSON('/api/menu/newbeers/'+$.cookie('location_id').replace(/^location:/,'').replace(/:/g,'/'),null,function(data,textStatus){
			show_new_beers($.cookie('location_id'));
		});
	}
	else {
		$('#setlocation_form').removeClass('hidden');
	}
}

</script>
<?php
include('../footer.php');
?>
