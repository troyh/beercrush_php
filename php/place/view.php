<?php
require_once('beercrush/beercrush.php');

$place_id='place:'.$_GET['place_id'];
$place_url=BeerCrush::docid_to_docurl($place_id);

$place   =BeerCrush::api_doc($BC->oak,$place_url);
$beerlist=BeerCrush::api_doc($BC->oak,$place_url.'/menu');
$reviews =BeerCrush::api_doc($BC->oak,'review/'.$place_url.'/0');
$photoset=BeerCrush::api_doc($BC->oak,'photoset/'.$place_url);
$nearby  =BeerCrush::api_doc($BC->oak,'nearby.fcgi?lat='.$place->address->latitude.'&lon='.$place->address->longitude.'&within=10');
$recommend=BeerCrush::api_doc($BC->oak,'recommend/'.$place_url);

// Sort nearby places by distance (closest first)
usort($nearby->places,'sort_by_distance');
// Remove this place (hopefully, the first one, or close to it, so we can quit early)
for ($i=0,$j=count($nearby->places);$i<$j;++$i) {
	if ($nearby->places[$i]->id==$place_id) {
		array_splice($nearby->places,$i,1); // Remove it
		break;
	}
}
// Truncate $nearby to the 4 closest places to this one (excluding this one, of course)
array_splice($nearby->places,4);

function distance_between_gps_coords($lat1,$lon1,$lat2,$lon2) {
	// Algorithm from http://www.movable-type.co.uk/scripts/latlong.html
	$r=6371; // km
	$dLat=deg2rad($lat2-$lat1);
	$dLon=deg2rad($lon2-$lon1); 
	$a=sin($dLat/2) * sin($dLat/2) +
	        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
	        sin($dLon/2) * sin($dLon/2); 
	$c = 2 * atan2(sqrt($a), sqrt(1-$a)); 
	$d = $r * $c;
	return $d;	
}

function sort_by_distance(&$a,&$b) {
	global $place;
	if (!isset($a->distance)) {
		// Compute distance from a to $place->address->latitude/longitude
		$a->distance=distance_between_gps_coords($a->lat,$a->lon,$place->address->latitude,$place->address->longitude);
	}
	
	if (!isset($b->distance)) {
		// Compute distance from b to $place->address->latitude/longitude
		$b->distance=distance_between_gps_coords($b->lat,$b->lon,$place->address->latitude,$place->address->longitude);
	}
	
	// FYI, doing math (subtraction) here doesn't work because the usort() function expects an integer, 
	// so 0.185 is considered equal to 0.195 (i.e., they're both 0 as ints). That's why it's done if/else style.
	if ($a->distance < $b->distance)
		return -1;
	if ($a->distance == $b->distance)
		return 0;
	return 1;
}


function sort_by_brewery($a,$b) {
	return strcmp($a->brewery->name,$b->brewery->name);
}

if (is_null($beerlist)) {
	$beerlist=new stdClass;
	$beerlist->items=array();
}
else {
	// Sort the beer list by brewery
	usort($beerlist->items,'sort_by_brewery');
}

// var_dump($photoset);exit;
// var_dump($beerlist);exit;
// var_dump($place);exit;
// var_dump($reviews);exit;

$header['title']=$place->name;
$header['js'][]='<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';
$header['js'][]='<script type="text/javascript" src="/js/jquery.uploadify.v2.1.0.js"></script>';
$header['js'][]='<script type="text/javascript" src="/js/swfobject.js"></script>';

// Add the CSS for Uploadify
$header['css'][]='<link href="/css/uploadify.css" rel="stylesheet" type="text/css" />';

function get_beer_doc($beer_id) {
	global $BC;
	return BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($beer_id));
}

include("../header.php");
?>

<div id="mwl">

<div id="main">

	<h1 id="place_name"><?=$place->name?></h1>
	<div class="cl"><?=$place->placetype?></div>

	<div class="cl"><div class="label">Crushworthiness</div><div style="float: left;"><span class="crush">97</span> <a class="tiny" href="" style="margin-left: 5px;">what is this?</a></div></div>
	<div class="cl"><div class="label"><a href="#ratings"><?=$place->review_summary->total?> ratings</a></div><div class="star_rating" style="float:left;"><div id="avgrating" style="width: <?=$place->review_summary->avg/5*100?>%"></div>(<?=$place->review_summary->avg?>)</div></div>
	<div class="cl"><div class="label">Atmosphere: </div><div class="smstar_rating"><div id="atmosphere" style="width: <?=$place->review_summary->atmosphere_avg/5*100?>%"></div>(<?=$place->review_summary->atmosphere_avg?>)</div></div>
	<div class="cl"><div class="label">Service: </div><div class="smstar_rating"><div id="service" style="width: <?=$place->review_summary->service_avg/5*100?>%"></div>(<?=$place->review_summary->service_avg?>)</div></div>
	<div class="cl"><div class="label">Food: </div><div class="smstar_rating"><div id="food" style="width: <?=$place->review_summary->food_avg/5*100?>%"></div>(<?=$place->review_summary->food_avg?>)</div></div>

<h2><?=count($beerlist->items)?> Beers on the Menu</h2>
<h3>Sort by <a href="" onclick="sort_beermenu('.brewery');return false;">Brewery</a> &#124; <a href="" onclick="sort_beermenu('.beername');return false;">Beer Name</a> &#124; <a href="" onclick="sort_beermenu('.servingtype');return false;">Served</a> &#124; <a href="" onclick="sort_beermenu('.rating',true);return false;">Rating</a></h3>
<p class="notice tiny">Last updated x days ago</p>

<ul id="beermenu">
	<?foreach ($beerlist->items as $item) :?>
	<li>
		<div class="served <?=$item->ontap     ?'ontap':''?> <?=$item->oncask    ?'oncask':''?> <?=$item->inbottle  ?'inbottle':''?> <?=$item->inbottle22?'inbottle22':''?> <?=$item->incan     ?'incan':''?>" title="Served <?=$item->ontap     ?'On Tap':''?> <?=$item->oncask    ?'On Cask':''?> <?=$item->inbottle  ?'In Bottles':''?> <?=$item->inbottle22?'In Large Bottles':''?> <?=$item->incan     ?'In Cans':''?>"></div>
		<span class="hidden servingtype"><?=$item->ontap?1:($item->oncask?2:($item->inbottle?3:($item->inbottle22?4:($item->incan?5:6))))?></span>
		<span class="brewery"><?=$item->brewery->name?></span><br />
		<a class="beername" href="/<?=BeerCrush::docid_to_docurl($item->id)?>"><?=$item->name?></a>
		<span class="rating"><?=get_beer_doc($item->id)->review_summary->avg?></span>
		<div class="price"><?=$item->price?'$'.number_format($item->price,2):'$?'?></div>
		<a href="" onclick="beerlist_delete('<?=$item->id?>',event);return false;" class="cmd" title="Remove from this menu"></a>
	</li>
	<?endforeach;?>
</ul>

<div>Update the Beer Menu:
	<div>Brewery: <input id="beerlist_new_brewery"    type="text" size="20" name="beerlist_new_brewery" /><input id="beerlist_new_brewery_id" type="hidden" name="beerlist_new_brewery_id" /></div>
	<div>Beer: <input id="beerlist_new_beer"    type="text" size="20" name="beerlist_new_beer" /><input id="beerlist_new_beer_id" type="hidden" name="beerlist_new_beer_id" /></div>
	<div>Price: $<input id="beerlist_new_price" type="text" size="4" name="beerlist_new_price" /></div>
	<div>Served:
	<select name="serving_<?=$item->id?>" size="1">
		<option value="tap" selected>On Tap</option>
		<option value="bottle">In Bottle</option>
		<option value="bottle22">In Large Bottle</option>
		<option value="can">In Can</option>
		<option value="cask">On Cask</option>
	</select>
	</div>
	<input type="button" onclick="beerlist_add(event,$('#beerlist_new_brewery').val(),$('#beerlist_new_beer').val(),$('#beerlist_new_price').val());" value="Add Beer" />

</div>

<h3 id="ratings"><?=count($reviews->reviews)?> Reviews</h3>
<?php foreach($reviews->reviews as $review):?>
<div class="areview">
	<img src="<?=$BC->docobj('user/'.$review->user_id)->avatar?>" /><span class="user"><a href="/user/<?=$review->user_id?>"><?=$BC->docobj('user/'.$review->user_id)->name?></a> posted <span class="datestring"><?=date('D, d M Y H:i:s O',$review->meta->timestamp)?></span></span>
	<div class="triangle-border top">
		<div class="star_rating"><div id="avgrating" style="width: <?=$review->rating?>0%"></div></div>
		<div><?=$review->comments?></div>
		<div class="cf"><div class="label">Service: </div><!--TROY TODO--></div>
		<div class="cf"><div class="label">Atmosphere: </div><!--TROY TODO--></div>
		<div class="cf"><div class="label">Food: </div><!--TROY TODO--></div>
	</div>
</div>
<?php endforeach?>

<h3>Add your review</h3>
<form id="review_form" method="post" action="/api/place/review">
	<input type="hidden" name="place_id" value="<?=$place->id?>">
	<div>Rating:
		<input type="radio" name="rating" value="1" />1 
		<input type="radio" name="rating" value="2" />2
		<input type="radio" name="rating" value="3" />3
		<input type="radio" name="rating" value="4" />4
		<input type="radio" name="rating" value="5" />5
	</div>
	<div>Service:
		
		<input type="radio" name="kidfriendly" value="1" />1 
		<input type="radio" name="kidfriendly" value="2" />2
		<input type="radio" name="kidfriendly" value="3" />3
		<input type="radio" name="kidfriendly" value="4" />4
		<input type="radio" name="kidfriendly" value="5" />5
	</div>
	<div>Atmosphere:
		
		<input type="radio" name="kidfriendly" value="1" />1 
		<input type="radio" name="kidfriendly" value="2" />2
		<input type="radio" name="kidfriendly" value="3" />3
		<input type="radio" name="kidfriendly" value="4" />4
		<input type="radio" name="kidfriendly" value="5" />5
	</div>
	<div>Food:
		
		<input type="radio" name="kidfriendly" value="1" />1 
		<input type="radio" name="kidfriendly" value="2" />2
		<input type="radio" name="kidfriendly" value="3" />3
		<input type="radio" name="kidfriendly" value="4" />4
		<input type="radio" name="kidfriendly" value="5" />5
	</div>
	<div>Comments:</div>
	<div><textarea name="comments" rows="5" cols="60"></textarea></div>
	
	<input type="submit" value="Post Rating" />
</form>
</div>
<div id="mwl_left_300">
	<div id="map"></div>
	<div id="streetview" style="width: 400px; height: 300px;"></div>
	<a href="" onclick="turn_street_view(false);return false;">Map</a> &#124;
	<a href="" onclick="turn_street_view(true);return false;">Street View</a> &#124;
	<a href="http://maps.google.com/maps?f=d&daddr=<?=$place->address->street.', '.$place->address->city.', '.$place->address->state.' '.$place->address->zip?>&hl=en">Get Directions</a> 
<div id="place">
	<input type="hidden" value="<?=$place->id?>" id="place_id">
	<span id="address">
		<div id="place_address:street"><?=$place->address->street?></div>
		<div><span id="place_address:city"><?=$place->address->city?></span>, <span id="place_address:state"><?=$place->address->state?></span>	<span id="place_address:zip"><?=$place->address->zip?></span></div>
		<div id="place_address:country"><?=$place->address->country?></div>
		<input type="hidden" name="latitude" value="<?=$place->address->latitude?>" />
		<input type="hidden" name="longitude" value="<?=$place->address->longitude?>" />
	</span>
	<div id="place_phone"><?=$place->phone?></div>
	<div><span id="place_uri" href="<?=$place->uri?>"><?=$place->uri?></span> <a href="<?=$place->uri?>">Visit web site</a></div>
	<div id="place_description"><?=$place->description?></div>
	
	<div class="cl"><div id="NEWplace_kid-friendly" class="ui-icon <?php echo isset($place->restaurant->kid_friendly)?($place->restaurant->kid_friendly?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Kid-Friendly</span></div>
	<div class="cl"><div id="NEWplace_outdoor-seating" class="ui-icon <?php echo isset($place->restaurant->outdoor_seating)?($place->restaurant->outdoor_seating?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Outdoor seating</span></div>
	<div class="cl"><div id="NEWplace_wi-fi" class="ui-icon <?php echo isset($place->wifi)?($place->wifi?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Wi-Fi</span></div>
	<div class="cl"><div id="NEWplace_bottles" class="ui-icon <?php echo isset($place->bottles)?($place->bottles?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Bottles to go</span></div>
	<div class="cl"><div id="NEWplace_growlers" class="ui-icon <?php echo isset($place->growlers)?($place->growlers?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Growlers to go</span></div>
	<div class="cl"><div id="NEWplace_kegs" class="ui-icon <?php echo isset($place->kegs)?($place->kegs?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Kegs to go</span></div>
	
	<p class="notice">editable method preserved below</p>
	
	<div class="cl"><div class="label">Kid-Friendly:</div><div id="place_kid-friendly"><?php echo isset($place->restaurant->kid_friendly)?($place->restaurant->kid_friendly?'Yes':'No'):'Unknown'; ?></div></div>
	<div class="cl"><div class="label">Outdoor seating: </div><div id="place_outdoor-seating"><?php echo isset($place->restaurant->outdoor_seating)?($place->restaurant->outdoor_seating?'Yes':'No'):'Unknown'; ?></div></div>
	<div class="cl"><div class="label">Wi-Fi: </div><div id="place_wi-fi"><?php echo isset($place->wifi)?($place->wifi?'Yes':'No'):'Unknown'; ?></div></div>
	<div class="cl"><div class="label">Bottles to go: </div><div id="place_bottles"><?php echo isset($place->bottles)?($place->bottles?'Yes':'No'):'Unknown'; ?></div></div>
	<div class="cl"><div class="label">Growlers to go: </div><div id="place_growlers"><?php echo isset($place->growlers)?($place->growlers?'Yes':'No'):'Unknown'; ?></div></div>
	<div class="cl"><div class="label">Kegs to go: </div><div id="place_kegs"><?php echo isset($place->kegs)?($place->kegs?'Yes':'No'):'Unknown'; ?></div></div>
		
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />
	<div id="editable_save_msg"></div>
</div>
<ul class="command">
	<li style="background-image: url('/img/wishlist.png')"><a href="">Bookmark this Place</a></li>
	<li style="background-image: url('/img/ratebeer.png')"><a href="">Rate this Place</a></li>
</ul>

<h3>Place Edit History</h3>
<div>Beer last modified: <span id="beer_lastmodified" class="datestring"><?=date('D, d M Y H:i:s O',$beerdoc->meta->mtime)?></span></div>
<div id="history"></div>


</div>
</div>
<div id="mwl_right_250">
<?php if (count($photoset->photos)==0):?><div id="photo_placeholder" class="place <?=$place->placetype?>"></div><?php else:?>
<?php foreach ($photoset->photos as $photo) :?>
	<div class="photo">
	<img src="<?=$photo->url?>?size=small" />
	<p class="caption"><a href="/user/<?=$photo->user_id?>"><?=$BC->docobj('user/'.$photo->user_id)->name?></a> <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$photo->timestamp)?></span></p>
	</div>
<?php endforeach; ?>
<?php endif;?>

<div id="new_photos"></div>

<input id="photo_upload" name="photo" type="file" />
<p></p>
	<h2>People Who Like This Place, Also Like</h2>
	<ul>
		<?php foreach ($recommend->place as $rp):
			$rpurl=BeerCrush::docid_to_docurl($rp);
			$rpdoc=BeerCrush::api_doc($BC->oak,$rpurl);
		?>
			<li><a href="/<?=$rpurl?>"><?=$rpdoc->name?></a></li>
		<?php endforeach;?>
	</ul>
	<h2>Other Beer Places Nearby</h2>
	<ul>
		<?php foreach ($nearby->places as $p):?>
			<li><a href="/<?=BeerCrush::docid_to_docurl($p->id)?>"><?=$p->name?></a> (<?=number_format($p->distance/1.609,2)?> miles)</li>
		<?php endforeach;?>
	</ul>
</div>


<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function beerlist_add(evt,brewery_name,beer_name,beer_price) {
	$.post('/api/menu/edit',{
		"place_id": $('#place_id').val(),
		"add_item": beerlist_new_beer_id+';;'+beer_price
	},
	function(data) {
		brewery_beerlist=[];
		brewery_beerlist_ids=[];
		beerlist_new_beer_id=null;

		if (data.items.length) {
			// Add a row to the beerlist table
			var newrow=$('#beerlist table tr').first().next().clone();
			var tds=$('td',$(newrow));

			$('a',$(tds[0])).attr('href','/'+data.items[data.items.length-1].brewery.id.replace(/:/g,'/')).text(data.items[data.items.length-1].brewery.name);

			$('a',$(tds[1])).attr('href','/'+data.items[data.items.length-1].id.replace(/:/g,'/')).text(data.items[data.items.length-1].name);
			$('input[type=button]',$(tds[1])).attr('onclick','').click(function(delevt){beerlist_delete(data.items[data.items.length-1].id,delevt);});

			if (data.items[data.items.length-1].price)
				$(tds[2]).text('$'+data.items[data.items.length-1].price.toFixed(2));
			else
				$(tds[2]).text('');

			var serving_types=["ontap","oncask","inbottle","inbottle22","incan"];
			
			for (i=0,j=serving_types.length;i<j;++i) {
				$('input',$(tds[i+3])).attr('name','serving_'+data.items[data.items.length-1].id).attr('checked',data.items[data.items.length-1][serving_types[i]]?"checked":"").change(beerlist_edit);
			}
		
			$('#beerlist table tr').last().prev().prev().after(newrow);
		}
		
		// Clear fields
		$('#beerlist_new_brewery').val('');
		$('#beerlist_new_brewery_id').val('');
		$('#beerlist_new_beer').val('');
		$('#beerlist_new_beer_id').val('');
		$('#beerlist_new_price').val('');
	});
}

function beerlist_delete(beer_id,evt) {
	$.post('/api/menu/edit',{
		"place_id": $('#place_id').val(),
		"del_item": beer_id
	},
	function(data) {
		$(evt.target).parents().filter('li').first().remove();
	},
	'json'
	);
}

function beerlist_edit(evt){
	var serving_types=[];
	$('#beerlist input[type="checkbox"][name='+$(evt.target).attr('name').replace(/:/g,'\\:')+']:checked').each(function(){serving_types.push($(this).val());});

	$.post('/api/menu/edit', {
		"place_id": $('#place_id').val(),
		"add_item": $(evt.target).attr('name').replace(/^serving_/,'')+';'+serving_types.join(',')
	});
}

var brewery_beerlist=[];
var brewery_beerlist_ids=[];
var beerlist_new_beer_id=null;

function geocodeAddress(callback) {
	var addressstr=$('#place_address\\:street').text() + ', ' +
		$('#place_address\\:city').text() + ', ' +
		$('#place_address\\:state').text() + ' ' +
		$('#place_address\\:zip').text() + ' ' +
		$('#place_address\\:country').text();

	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({
		address: addressstr
	},
	callback);
    
}

var place_latitude=<?=$place->address->latitude?$place->address->latitude:'null'?>;
var place_longitude=<?=$place->address->longitude?$place->address->longitude:'null'?>;
var map=null;

function makemap(lat,lon) {
	var latlng = new google.maps.LatLng(lat,lon);
	var myOptions = {
		zoom: 10,
		center: latlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		streetViewControl: true
	  
	};
	map = new google.maps.Map(document.getElementById("map"), myOptions);
	var infowindow=new google.maps.InfoWindow({
		content: "<div id=\"bodyContent\">"+
		"<a href=\"http://maps.google.com/maps?f=d&daddr=<?=$place->address->street.', '.$place->address->city.', '.$place->address->state.' '.$place->address->zip?>&hl=en\">Get Directions</a>  &#124; "+
		"<a href=\"http://maps.google.com/maps?f=q&hl=en&q=<?=$place->address->street.', '.$place->address->city.', '.$place->address->state.' '.$place->address->zip?>\">Street View</a>"
	});
	var marker=new google.maps.Marker({
		position: latlng,
		map: map,
		title: "<?=$place->name?>"
	});
	google.maps.event.addListener(marker,'click',function(){
		infowindow.open(map,marker);
	})
}

function turn_street_view(turnon) {
	if (turnon) {
		var pos=new google.maps.LatLng(place_latitude,place_longitude);
		var panoptions={
			position:pos,
			pov: {
				heading: 34,
				pitch: 10,
				zoom: 1
			}
		};
		var panorama = new  google.maps.StreetViewPanorama(document.getElementById("streetview"), panoptions);
		map.setStreetView(panorama);
		$('#map').hide();
		$('#streetview').show();
	}
	else {
		$('#map').show();
		$('#streetview').hide();
	}
}

function updateLatLon(results,status) {
	if (status == google.maps.GeocoderStatus.OK) {
		place_latitude=results[0].geometry.location.lat();
		place_longitude=results[0].geometry.location.lng();
		makemap(place_latitude,place_longitude);

		$.post('/api/place/edit',{
			'place_id': $('#place_id').val(),
			'address:latitude': place_latitude,
			'address:longitude': place_longitude
		},function(data){
		});
	}
}


function sort_beermenu(selector,reverse) {
	var mylist = $('#beermenu');
	var listitems = mylist.children('li').get();
	listitems.sort(function(a, b) {
	   var compA = $(a).children(selector).first().text().toUpperCase();
	   var compB = $(b).children(selector).first().text().toUpperCase();
	   return ((compA < compB) ? -1 : (compA > compB) ? 1 : 0) * (reverse?-1:1);
	})
	$.each(listitems, function(idx, itm) { mylist.append(itm); });
}


function pageMain()
{
	$('#streetview').hide();
	
	if (place_longitude && place_latitude) {
		makemap(place_latitude,place_longitude)
	}
	else {
		geocodeAddress(updateLatLon);
	}

	makeDocEditable('#place','place_id','/api/place/edit',{
		'afterSave': function() {
			geocodeAddress(updateLatLon);
		}
	});
	
	$('#beerlist input[type=checkbox]').change(beerlist_edit);
	
	$('#beerlist_new_brewery').autocomplete('/api/autocomplete.fcgi',{
		"mustMatch": true,
		"extraParams": {
			"dataset": "breweries"
		}
	}).result(function(evt,data,formatted) {
		brewery_beerlist=[];
		brewery_beerlist_ids=[];

		$('#beerlist_new_beer').flushCache();
		
		$.getJSON('/api/search',{
			"q": jQuery.isArray(data)?data[0]:data,
			"dataset": "brewery"
		},
		function (data,status) {
			if (data.response.docs.length) {
				var brewery_id=data.response.docs[0].id;
				$.getJSON('/api/brewery/'+brewery_id.replace(/^brewery:/,'')+'/beerlist',function(data,status){
					$(data.beers).each(function(i,v){
						brewery_beerlist.push(v.name);
						brewery_beerlist_ids[v.name]=v.beer_id;
					});
					$('#beerlist_new_beer').autocomplete(brewery_beerlist).result(function(evt,data,formatted) {
						beerlist_new_beer_id=brewery_beerlist_ids[data];
					});
				});
			}
		});
	}
	);

	$('#review_form').submit(function(){
		$.post($('#review_form').attr('action'),
		$('#review_form').serialize(),
		function(data,status){
			
		});
		return false;
	});

	$('#photo_upload').uploadify({
		'uploader'  : '/flash/uploadify.swf',
		'script'    : '/api/place/photo',
		'cancelImg' : '/img/uploadify/cancel.png',
		'auto'      : true,
		'multi'     : true,
		'fileDataName': 'photo',
		'fileDesc'	: 'POST A PHOTO',
		'fileExt'	: '*.jpg;*.jpeg;*.png',
		'buttonText': "POST A PHOTO", 
		'sizeLimit' : 5000000, 
		'scriptData': {
			'place_id': $('#place_id').val(),
			'userid': $.cookie('userid')
		},
		'onComplete': function(evt,queueID,fileObj,response,data) {
			photoinfo=$.parseJSON(response);
			$('#new_photos').append('<img src="'+photoinfo.url+'?size=small" />');
			return true;
		}
	});
	

}

</script>
<?
include("../footer.php");
?>