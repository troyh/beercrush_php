<?php
// TODO: let downstream proxies cache this page, but we shouldn't cache this page at the server (too many different URLs)
require_once 'beercrush/beercrush.php';

$header['js'][]='<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';
// $header['js'][]='<script type="text/javascript" src="/js/jquery.bt.js"></script>';
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

<div id="map_canvas" style="float:right;width:500px;height:500px;"></div>
<ul id="nearbylist" style="overflow:auto;width:440px;height:500px;">
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
				// TODO: handle this: Browser failed to provide location
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
var map=null;
var openinfowindow=null;

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
	if ((start+j) < places_list.length) {
		$('#pagenav_showing').html('Showing '+(start+j)+' places (of '+places_list.length+') within '+settings.within+' miles. <input type="button" value="Show '+Math.min(count,places_list.length-(start+count))+' more" onclick="display_list(\''+sel+'\','+(start+count)+','+count+');" />');
	}
	else {
		$('#pagenav_showing').html('Showing all '+places_list.length+' places within '+settings.within+' miles. Extend range:'+
		'<select onchange="requery_url($(this).val());">'+
		'<option value="'+(settings.within*2)+'">'+(settings.within*2)+' miles</option>'+
		'<option value="'+(settings.within*3)+'">'+(settings.within*3)+' miles</option>'+
		'<option value="'+(settings.within*4)+'">'+(settings.within*4)+' miles</option>'+
		'</select>');
	}
		
	var placetype_strings=[
		"",
		"Bar",
		"Brewpub",
		"Restaurant",
		"Store"
	];
		
	for (var i=start;i<(start+j);++i) {
		var id=places_list[i].id.replace(/:/g,'-');
		$(sel).append('<li id="'+id+'"><a href="/'+places_list[i].id.replace(/:/g,'/')+'">'+places_list[i].name+'</a> ['+placetype_strings[places_list[i].placetype]+'] ('+(places_list[i].distance*0.621371192).toFixed(2)+' mi / '+places_list[i].distance.toFixed(2)+' km)</li>');
		var marker=new google.maps.Marker({
			position: new google.maps.LatLng(places_list[i].lat,places_list[i].lon),
			map: map
		});
		makeInfoWindow(marker,places_list[i]);
		makeListItemClickable(id,marker);
	}
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
		
		$('#nearbylist').spinner();
		$.getJSON('/api/nearby.fcgi',{
			within:settings.within,
			lat: settings.centerpoint.lat, 
			lon: settings.centerpoint.lon
		},function(data,textStatus){
			$('#nearbylist').spinner('close');
			places_list=data.places;
			// Calculate distance for each place
			for (var i=0;i<places_list.length;++i) {
				places_list[i].distance=gps_distance(
					{lat:places_list[i].lat,lon:places_list[i].lon},
					{lat:settings.centerpoint.lat,lon:settings.centerpoint.lon}
				);
			}
			places_list.sort(function(a,b){return a.distance-b.distance});
			
			// Draw map and put places on map
			var opts={
				zoom: 12,
				center: latlng,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			map=new google.maps.Map(document.getElementById('map_canvas'),opts);

			// var loc_marker=new google.maps.Marker({
			// 	position: latlng,
			// 	map: map,
			// 	title: 'Your location'
			// });
			
			display_list('#nearbylist',0,settings.display_limit);
	
		});
	}
	
}

function makeInfoWindow(marker,place) {
	var infowindow=new google.maps.InfoWindow({
		content: place.name,
		size: new google.maps.Size(50,50)
	});
	google.maps.event.addListener(marker,'click',function(){
		$.getJSON('/api/'+place.id.replace(/:/,'/'),null,function(place){
			
			// Close any other infowindows
			if (openinfowindow)
				openinfowindow.close();
	
			var addr='';
			if (place.address.street)
				addr+=place.address.street+'<br />';
			addr+=place.address.city+', '+place.address.state+' '+place.address.zip;
			if (place.phone)
				addr+='<br />'+place.phone;

			infowindow.setContent('<div id="bt_content_wrapper" style="height:200px">'+
			'<div id="bt_content">'+
				'<h3 id="bt_content_name">'+place.name+'</h3>'+
				'<div id="bt_content_placetype">'+place.placetype+'</div>'+
				'<a id="bt_content_url" href="'+place.url+'">More Info</a>'+
				'<hr />'+
				'<div>Rating: <span id="bt_content_avg">'+place.review_summary.avg+'</span></div>'+
				'<div>Food: <span id="bt_content_food_avg">'+place.review_summary.food_avg+'</span></div>'+
				'<div>Service: <span id="bt_content_service_avg">'+place.review_summary.service_avg+'</span></div>'+
				'<div>Atmosphere: <span id="bt_content_atmosphere_avg">'+place.review_summary.atmosphere_avg+'</span></div>'+
				'<hr />'+
				'<div id="bt_content_address">'+addr+'</div>'+
			'</div>'+
			'</div>');
			
			infowindow.open(map,marker);
			openinfowindow=infowindow;
		});
	});

}

function makeListItemClickable(id,marker) {
	$('#'+id).click(function(evt){
		google.maps.event.trigger(marker,'click');
		return false;
	});
}

</script>

<?php include("./footer.php"); ?>
