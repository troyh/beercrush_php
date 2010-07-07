<?php
// TODO: let downstream proxies cache this page, but we shouldn't cache this page at the server (too many different URLs)
require_once 'beercrush/beercrush.php';

$header['js'][]='<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';
$header['js'][]='<script type="text/javascript" src="/js/jquery.bt.js"></script>';
$header['js'][]='<!--[if IE]><script type="text/javascript" src="/js/excanvas.js"></script><![endif]-->';
include('./header.php');
?>

<a href="" onclick="$('#setlocation_form').toggleClass('hidden');return false;">Change my location</a>
<div id="setlocation_form" class="hidden">
	<input id="location_box" type="text" size="10" value="" /><input type="button" onclick="geolocate($('#location_box').val());" value="Go" />
	<a href="" onclick="geolocate();return false;">Ask my browser</a>
</div>

<h1>Places Near <span id="location_text"></span></h1>

<div id="nolocation_msg" class="hidden">
	<h2>Your location is not known.</h2>
	<a href="foo" onclick="$('#setlocation_form').removeClass('hidden');return false;">Locate me</a>
</div>

<ul id="nearbylist">
</ul>
<div id="pagenav_showing"></div>

<div class="hidden">
<div id="bt_content">
	<h3 id="bt_content_name"></h3>
	<div id="bt_content_placetype"></div>
	<a id="bt_content_url" href=""></a>
	<hr />
	<div>Rating: <span id="bt_content_avg"></span></div>
	<div>Food: <span id="bt_content_food_avg"></span></div>
	<div>Service: <span id="bt_content_service_avg"></span></div>
	<div>Atmosphere: <span id="bt_content_atmosphere_avg"></span></div>
	<hr />
	<div id="bt_content_address"></div>
</div>
</div>

<script type="text/javascript" charset="utf-8">

function shownearbyplaces(lat,lon) {
	window.location.href='/nearby?lat='+lat+'&lon='+lon;
	return;
	$.getJSON('/api/nearby.fcgi',{
		'lat': lat,
		'lon': lon
	},
	function (data,textStatus) {
	});
}

$.extend({
  getUrlVars: function(){
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  },
  getUrlVar: function(name){
    return $.getUrlVars()[name];
  }
});

function geolocate(str) {
	if (typeof(str)=='string' && str.length) {

		var geocoder = new google.maps.Geocoder();
		geocoder.geocode({
			address: str
		},
		function(results,status){
			if (status==google.maps.GeocoderStatus.OK) {
				$.cookie('location_lat',results[0].geometry.location.lat());
				$.cookie('location_lon',results[0].geometry.location.lng());
				shownearbyplaces(results[0].geometry.location.lat(),results[0].geometry.location.lng());
			}
		});
		
	}
	else {
		if(navigator.geolocation) {
			browserSupportFlag = true;
			navigator.geolocation.getCurrentPosition(function(position) {
				$.cookie('location_lat',position.coords.latitude);
				$.cookie('location_lon',position.coords.longitude);
				shownearbyplaces(position.coords.latitude,position.coords.longitude)
			}, function() {
				// Browser failed to provide location
			});
		}
	}
	return false;
}

var settings={
	centerpoint: {},
	within: 5,
	display_limit: 20
};
var places_list=null;

function toRad(d) {
	return d * (Math.PI/180);
}

function gps_distance(loc1,loc2) {
	// Formula from http://www.movable-type.co.uk/scripts/latlong.html
	var R = 6371; // km
	var dLat = toRad(loc2.lat-loc1.lat);
	var dLon = toRad(loc2.lon-loc1.lon); 
	var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
	        Math.cos(toRad(loc1.lat)) * Math.cos(toRad(loc2.lat)) * 
	        Math.sin(dLon/2) * Math.sin(dLon/2); 
	var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
	var d = R * c;
	return d;
}

function requery_url(within) {
	window.location.href='?lat='+settings.centerpoint.lat+'&lon='+settings.centerpoint.lon+'&within='+within;
}
function display_list(sel,start,count) {
	var j=Math.min(count,places_list.length-start);
	if ((start+j) < places_list.length)
		$('#pagenav_showing').html('Showing '+(start+j)+' places (of '+places_list.length+') within '+settings.within+' miles. <input type="button" value="Show '+Math.min(count,places_list.length-(start+count))+' more" onclick="display_list(\''+sel+'\','+(start+count)+','+count+');" />');
	else
		$('#pagenav_showing').html('Showing all '+places_list.length+' places within '+settings.within+' miles. Extend range:'+
		'<select onchange="requery_url($(this).val());">'+
		'<option value="'+(settings.within*2)+'">'+(settings.within*2)+' miles</option>'+
		'<option value="'+(settings.within*3)+'">'+(settings.within*3)+' miles</option>'+
		'<option value="'+(settings.within*4)+'">'+(settings.within*4)+' miles</option>'+
		'</select>');
		
	var placetype_strings=[
		"",
		"Bar",
		"Brewpub",
		"Restaurant",
		"Store"
	];
		
	for (var i=start;i<(start+j);++i) {
		$(sel).append('<li><a href="/'+places_list[i].id.replace(/:/g,'/')+'">'+places_list[i].name+'</a> ['+placetype_strings[places_list[i].placetype]+'] ('+(places_list[i].distance*0.621371192).toFixed(2)+' mi / '+places_list[i].distance.toFixed(2)+' km)</li>');
	}
	
	$('#nearbylist li a').click(function(evt){

		var atag=$(this);
		$.getJSON('/api/'+$(this).attr('href'),null,function(place){
			$('#bt_content_name').html(place.name);
			$('#bt_content_placetype').html(place.placetype);
			$('#bt_content_avg').html(place.review_summary.avg);
			$('#bt_content_food_avg').html(place.review_summary.food_avg);
			$('#bt_content_service_avg').html(place.review_summary.service_avg);
			$('#bt_content_atmosphere_avg').html(place.review_summary.atmosphere_avg);
			var addr='';
			if (place.address.street)
				addr+=place.address.street+'<br />';
			addr+=place.address.city+', '+place.address.state+' '+place.address.zip;
			if (place.phone)
				addr+='<br />'+place.phone;
				
			$('#bt_content_address').html(addr);
			$('#bt_content_url').attr('href',$(atag).attr('href'));
			$('#bt_content_url').text('More Info');
			
			$('#bt_content_wrapper').html($('#bt_content').html());
		});
		
		$(this).bt('<div id="bt_content_wrapper" style="height:200px"></div>',{
			trigger: 'none',
			closeWhenOthersOpen: true,
			centerPointY: .1,
			positions: ['right', 'left'], 
			padding: 10, 
			width: 256, 
			spikeGirth: 30, 
			spikeLength: 75, 
			cornerRadius: 10, 
			fill: '#FFF', 
			strokeStyle: '#B9090B', 
			shadow: true, 
			shadowBlur: 12,
			shadowOffsetX: 0,
			shadowOffsetY: 5, 
			cssStyles: {
				fontSize: '12px',
			}
		}).btOn();
		
		return false; // Prevent nav to the href
	});
	
}

function pageMain() {
	
	if (typeof($.getUrlVar('lat'))=='undefined' || typeof($.getUrlVar('lon'))=='undefined') {
		// URL doesn't specify a location, see if we already have one for this user
		if ($.cookie('location_lat') && $.cookie('location_lon')) {
			shownearbyplaces($.cookie('location_lat'),$.cookie('location_lon'));
		}
		else {
			// Tell the user we don't know their location and offer to locate them
			$('#nolocation_msg').removeClass('hidden');
		}
	}
	else {
		settings.centerpoint.lat=$.getUrlVar('lat');
		settings.centerpoint.lon=$.getUrlVar('lon');
		
		// What does Google Maps call this location?
		var latlng=new google.maps.LatLng(settings.centerpoint.lat,settings.centerpoint.lon);
		var geocoder=new google.maps.Geocoder();
		geocoder.geocode({'latLng': latlng}, function(results,status){
			if (status==google.maps.GeocoderStatus.OK) {
				$('#location_text').html(results[0].formatted_address);
			}
		});
		
		if (typeof($.getUrlVar('within'))!='undefined' && $.getUrlVar('within').length)
			settings.within=$.getUrlVar('within');
		
		$.getJSON('/api/nearby.fcgi',{
			within:settings.within,
			lat: settings.centerpoint.lat, 
			lon: settings.centerpoint.lon
		},function(data,textStatus){
			places_list=data.places;
			// Calculate distance for each place
			for (var i=0;i<places_list.length;++i) {
				places_list[i].distance=gps_distance(
					{lat:places_list[i].lat,lon:places_list[i].lon},
					{lat:settings.centerpoint.lat,lon:settings.centerpoint.lon}
				);
			}
			places_list.sort(function(a,b){return a.distance-b.distance});
			display_list('#nearbylist',0,settings.display_limit);
		});
	}
	
}
	
</script>

<?php include("./footer.php"); ?>
