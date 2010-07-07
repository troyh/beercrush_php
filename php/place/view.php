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
$header['css'][]='<link href="/css/jquery.ui.stars.css" rel="stylesheet" type="text/css" />';

// Add the CSS for Uploadify
$header['css'][]='<link href="/css/uploadify.css" rel="stylesheet" type="text/css" />';

function get_beer_doc($beer_id) {
	global $BC;
	return BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($beer_id));
}

function true_false_blank($val) {
	if ($val===true)
		print 'true';
	else if ($val===false)
		print 'false';
	else
		print '';
}

include("../header.php");
?>

<div id="mwl">

<div id="main">

	<h1><?=$place->name?></h1>
	<div class="cl"><?=$place->placetype?></div>

	<div class="cl"><div class="label">Crushworthiness</div><div style="float: left;"><span class="crush">97</span> <a class="tiny" href="" style="margin-left: 5px;">what is this?</a></div></div>
	<div class="cl"><div class="label"><a href="#ratings"><?=$place->review_summary->total?> ratings</a></div><div class="star_rating" style="float:left;" title="Rating: <?=$place->review_summary->avg?> of 5"><div id="avgrating" style="width: <?=$place->review_summary->avg/5*100?>%"></div></div></div>
	<div class="cl"><div class="label">Your Predicted Rating</div><div class="star_rating" style="float:left;" title=""><div id="predrating" style="width: 0%"></div></div></div>
	<div class="cl"><div class="label">Atmosphere: </div><div class="smstar_rating" title="Atmosphere Rating: <?=$place->review_summary->atmosphere_avg?> of 5"><div id="atmosphere" style="width: <?=$place->review_summary->atmosphere_avg/5*100?>%"></div></div></div>
	<div class="cl"><div class="label">Service: </div><div class="smstar_rating" title="Service Rating: <?=$place->review_summary->service_avg?> of 5"><div id="service" style="width: <?=$place->review_summary->service_avg/5*100?>%"></div></div></div>
	<div class="cl"><div class="label">Food: </div><div class="smstar_rating" title="Food Rating: <?=$place->review_summary->food_avg?> of 5"><div id="food" style="width: <?=$place->review_summary->food_avg/5*100?>%"></div></div></div>

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
		<span class="rating"><div class="star_rating" title="Rating: <?=get_beer_doc($item->id)->review_summary->avg?> of 5"><div class="avgrating" style="width: <?=get_beer_doc($item->id)->review_summary->avg/5*100?>%"></div></div></span>
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

<div id="review_page" class="hidden">
<form id="review_form" method="post" action="/api/place/review">
	<input type="hidden" name="place_id" value="" />
	<div class="cf">
		<div class="label">Rating:</div>
		<div id="rating-wrapper">
			<input type="radio" name="rating" value="1" /> 
			<input type="radio" name="rating" value="2" />
			<input type="radio" name="rating" value="3" />
			<input type="radio" name="rating" value="4" />
			<input type="radio" name="rating" value="5" />
		</div>
	</div>
	<div class="cf">
		<div class="label">Service:</div>
		<div id="service-wrapper">
			<input type="radio" name="service" value="1" /> 
			<input type="radio" name="service" value="2" />
			<input type="radio" name="service" value="3" />
			<input type="radio" name="service" value="4" />
			<input type="radio" name="service" value="5" />
		</div>
	</div>
	<div class="cf">
		<div class="label">Atmosphere:</div>
		<div id="atmosphere-wrapper">
			<input type="radio" name="atmosphere" value="1" /> 
			<input type="radio" name="atmosphere" value="2" />
			<input type="radio" name="atmosphere" value="3" />
			<input type="radio" name="atmosphere" value="4" />
			<input type="radio" name="atmosphere" value="5" />
		</div>
	</div>
	<div class="cf">
		<div class="label">Food:</div>
		<div id="food-wrapper">
			<input type="radio" name="food" value="1" /> 
			<input type="radio" name="food" value="2" />
			<input type="radio" name="food" value="3" />
			<input type="radio" name="food" value="4" />
			<input type="radio" name="food" value="5" />
		</div>
	</div>
	<div>Comments:</div>
	<div><textarea name="comments" rows="5" cols="40"></textarea></div>
	
	<input id="post_review_button" type="button" value="Post Rating" />
</form>
</div>

</div>
<div id="mwl_left_300">
	<div id="map"></div>
	<div id="streetview" style="width: 400px; height: 300px;"></div>
	<a href="" onclick="turn_street_view(false);return false;">Map</a> &#124;
	<a href="" onclick="turn_street_view(true);return false;">Street View</a> &#124;
	<a href="http://maps.google.com/maps?f=d&daddr=<?=$place->address->street.', '.$place->address->city.', '.$place->address->state.' '.$place->address->zip?>&hl=en">Get Directions</a> 
<div><input type="button" id="edit_button" value="Edit This" /></div>
<div id="place">
	<input type="hidden" value="<?=$place->id?>" id="place_id">
	<input type="hidden" value="<?=$place->name?>" id="place_name">
	<input type="hidden" id="place_kidfriendly" value="<?=true_false_blank($place->kid_friendly)?>" />
	<input type="hidden" id="place_outdoorseating" value="<?=true_false_blank($place->restaurant->outdoor_seating)?>" />
	<input type="hidden" id="place_wifi" value="<?=true_false_blank($place->wifi)?>" />
	<input type="hidden" id="place_bottles" value="<?=true_false_blank($place->togo->bottles)?>" />
	<input type="hidden" id="place_growlers" value="<?=true_false_blank($place->togo->growlers)?>" />
	<input type="hidden" id="place_kegs" value="<?=true_false_blank($place->togo->kegs)?>" />
	<span id="address">
		<div id="place_address_street"><?=$place->address->street?></div>
		<div><span id="place_address_city"><?=$place->address->city?></span>, <span id="place_address_state"><?=$place->address->state?></span>	<span id="place_address_zip"><?=$place->address->zip?></span></div>
		<div id="place_address_country"><?=$place->address->country?></div>
		<input type="hidden" name="latitude" value="<?=$place->address->latitude?>" />
		<input type="hidden" name="longitude" value="<?=$place->address->longitude?>" />
	</span>
	<div id="place_phone"><?=$place->phone?></div>
	<div><span id="place_uri" href="<?=$place->uri?>"><?=$place->uri?></span> <a href="<?=$place->uri?>">Visit web site</a></div>
	<div id="place_description"><?=$place->description?></div>
	
	<div class="cl"><div id="place_kidfriendly_icon" class="ui-icon <?php echo isset($place->kid_friendly)?($place->kid_friendly?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Kid-Friendly</span></div>
	<div class="cl"><div id="place_outdoorseating_icon" class="ui-icon <?php echo isset($place->restaurant->outdoor_seating)?($place->restaurant->outdoor_seating?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Outdoor seating</span></div>
	<div class="cl"><div id="place_wifi_icon" class="ui-icon <?php echo isset($place->wifi)?($place->wifi?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Wi-Fi</span></div>
	<div class="cl"><div id="place_bottles_icon" class="ui-icon <?php echo isset($place->togo->bottles)?($place->togo->bottles?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Bottles to go</span></div>
	<div class="cl"><div id="place_growlers_icon" class="ui-icon <?php echo isset($place->togo->growlers)?($place->togo->growlers?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Growlers to go</span></div>
	<div class="cl"><div id="place_kegs_icon" class="ui-icon <?php echo isset($place->togo->kegs)?($place->togo->kegs?'ui-icon-check':'ui-icon-closethick'):'ui-icon-help'; ?>"></div><span class="label">Kegs to go</span></div>
</div>

<div id="place_edit" class="hidden">

	<input id="place_name_edit" type="text" value="<?=$place->name?>" />
	<div><input id="place_address_street_edit" type="text" value="<?=$place->address->street?>" /></div>
	<div><input id="place_address_city_edit" type="text" value="<?=$place->address->city?>" />, 
		<input id="place_address_state_edit" type="text" value="<?=$place->address->state?>" />
		<input id="place_address_zip_edit" type="text" value="<?=$place->address->zip?>" /></div>
	<input id="place_address_country_edit" type="text" value="<?=$place->address->country?>" />
	<input id="place_phone_edit" type="text" value="<?=$place->phone?>" />

	<div class="cl"><div class="label">Kid-Friendly:</div><div>
		<input type="hidden" id="place_kidfriendly_edit" value="<?=(is_bool($place->kid_friendly))?($place->kid_friendly?"true":"false"):""?>" />
		<input type="radio" name="kidfriendly" value="true"  <?php if (isset($place->kid_friendly) &&  $place->kid_friendly):?>checked="checked"<?php endif;?>>Yes
		<input type="radio" name="kidfriendly" value="false" <?php if (isset($place->kid_friendly) && !$place->kid_friendly):?>checked="checked"<?php endif;?>>No
		<input type="radio" name="kidfriendly" value="" <?php if (!isset($place->kid_friendly)):?>checked="checked"<?php endif;?>>Don't Know
	</div>
	</div>
	
	<div class="cl"><div class="label">Outdoor seating: </div><div>
		<input type="hidden" id="place_outdoorseating_edit" value="<?=(isset($place->restaurant->outdoor_seating))?($place->restaurant->outdoor_seating?"true":"false"):""?>" />
		<input type="radio" name="outdoorseating" value="true"  <?php if (isset($place->restaurant->outdoor_seating) &&  $place->restaurant->outdoor_seating):?>checked="checked"<?php endif;?>>Yes
		<input type="radio" name="outdoorseating" value="false" <?php if (isset($place->restaurant->outdoor_seating) && !$place->restaurant->outdoor_seating):?>checked="checked"<?php endif;?>>No
		<input type="radio" name="outdoorseating" value="" <?php if (!isset($place->restaurant->outdoor_seating)):?>checked="checked"<?php endif;?>>Don't Know
	</div>
	</div>
	
	<div class="cl"><div class="label">Wi-Fi: </div><div>
		<input type="hidden" id="place_wifi_edit" value="<?=(isset($place->wifi))?($place->wifi?"true":"false"):""?>" />
		<input type="radio" name="wifi" value="true"  <?php if (isset($place->wifi) &&  $place->wifi):?>checked="checked"<?php endif;?>>Yes
		<input type="radio" name="wifi" value="false" <?php if (isset($place->wifi) && !$place->wifi):?>checked="checked"<?php endif;?>>No
		<input type="radio" name="wifi" value="" <?php if (!isset($place->wifi)):?>checked="checked"<?php endif;?>>Don't Know
	</div>
	</div>

	<div class="cl"><div class="label">Bottles to go: </div><div>
		<input type="hidden" id="place_bottles_edit" value="<?=(isset($place->togo->bottles))?($place->togo->bottles?"true":"false"):""?>" />
		<input type="radio" name="bottles" value="true"  <?php if (isset($place->togo->bottles) &&  $place->togo->bottles):?>checked="checked"<?php endif;?>>Yes
		<input type="radio" name="bottles" value="false" <?php if (isset($place->togo->bottles) && !$place->togo->bottles):?>checked="checked"<?php endif;?>>No
		<input type="radio" name="bottles" value="" <?php if (!isset($place->togo->bottles)):?>checked="checked"<?php endif;?>>Don't Know
	</div>
	</div>
	
	<div class="cl"><div class="label">Growlers to go: </div><div>
		<input type="hidden" id="place_growlers_edit" value="<?=(isset($place->togo->growlers))?($place->togo->growlers?"true":"false"):""?>" />
		<input type="radio" name="growlers" value="true"  <?php if (isset($place->togo->growlers) &&  $place->togo->growlers):?>checked="checked"<?php endif;?>>Yes
		<input type="radio" name="growlers" value="false" <?php if (isset($place->togo->growlers) && !$place->togo->growlers):?>checked="checked"<?php endif;?>>No
		<input type="radio" name="growlers" value="" <?php if (!isset($place->togo->growlers)):?>checked="checked"<?php endif;?>>Don't Know
	</div>
	</div>
	
	<div class="cl"><div class="label">Kegs to go: </div><div>
		<input type="hidden" id="place_kegs_edit" value="<?=(isset($place->togo->kegs))?($place->togo->kegs?"true":"false"):""?>" />
		<input type="radio" name="kegs" value="true"  <?php if (isset($place->togo->kegs) &&  $place->togo->kegs):?>checked="checked"<?php endif;?>>Yes
		<input type="radio" name="kegs" value="false" <?php if (isset($place->togo->kegs) && !$place->togo->kegs):?>checked="checked"<?php endif;?>>No
		<input type="radio" name="kegs" value="" <?php if (!isset($place->togo->kegs)):?>checked="checked"<?php endif;?>>Don't Know
	</div>
	</div>
	
</div>
	
<ul class="command">
	<li style="background-image: url('/img/wishlist.png')"><a id="bookmark_link" href="">Bookmark this Place</a></li>
	<li style="background-image: url('/img/ratebeer.png')"><a id="rateplace_link" href="">Rate this Place</a></li>
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

function booleans_click_handler() {
	console.log('booleans_click_handler:'+$(this).attr('name'));
	$('#place_'+$(this).attr('name')+'_icon').addClass('ui-icon-help'); // By default, make it unknown, it'll get set appropriately in postSuccess
	$('#place_'+$(this).attr('name')+'_edit').val($(this).val().length?$(this).val():"");
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


	// Make edit radio buttons set the value for the edit field
	$('#place_edit input[name=kidfriendly]:radio').change(booleans_click_handler);
	$('#place_edit input[name=outdoorseating]:radio').change(booleans_click_handler);
	$('#place_edit input[name=wifi]:radio').change(booleans_click_handler);
	$('#place_edit input[name=bottles]:radio').change(booleans_click_handler);
	$('#place_edit input[name=growlers]:radio').change(booleans_click_handler);
	$('#place_edit input[name=kegs]:radio').change(booleans_click_handler);

	$('#place').editabledoc('/api/place/edit',{
		args: {
			place_id: $('#place_id').val()
		},
		stripprefix: 'place_',
		fields: {
			'place_name': {
				postSuccess: function(name,value) {
					$('#main h1').html(value); // Change the H1 tag on the page (the place name)
				}
			},
			'place_kidfriendly': { 
				postName: 'kid_friendly',
				postSuccess: function(name,value) {
					$('#place_kidfriendly_icon').removeClass('ui-icon-check ui-icon-closethick ui-icon-help');
					if (value==true)
						$('#place_kidfriendly_icon').addClass('ui-icon-check');
					else if (value==false)
						$('#place_kidfriendly_icon').addClass('ui-icon-closethick');
					else
						$('#place_kidfriendly_icon').addClass('ui-icon-help');
				}
			},
			'place_outdoorseating': { 
				postName: 'restaurant:outdoor_seating',
				postSuccess: function(name,value) {
					$('#place_outdoorseating_icon').removeClass('ui-icon-check ui-icon-closethick ui-icon-help');
					if (value==true)
						$('#place_outdoorseating_icon').addClass('ui-icon-check');
					else if (value==false)
						$('#place_outdoorseating_icon').addClass('ui-icon-closethick');
					else
						$('#place_outdoorseating_icon').addClass('ui-icon-help');
				}
			},
			'place_wifi': { 
				postName: 'wifi',
				postSuccess: function(name,value) {
					$('#place_wifi_icon').removeClass('ui-icon-check ui-icon-closethick ui-icon-help');
					if (value==true)
						$('#place_wifi_icon').addClass('ui-icon-check');
					else if (value==false)
						$('#place_wifi_icon').addClass('ui-icon-closethick');
					else
						$('#place_wifi_icon').addClass('ui-icon-help');
				}
			 },
			'place_bottles': { 
				postName: 'togo:bottles', 
				postSuccess: function(name,value) {
					$('#place_bottles_icon').removeClass('ui-icon-check ui-icon-closethick ui-icon-help');
					if (value==true)
						$('#place_bottles_icon').addClass('ui-icon-check');
					else if (value==false)
						$('#place_bottles_icon').addClass('ui-icon-closethick');
					else
						$('#place_bottles_icon').addClass('ui-icon-help');
				}
			},
			'place_growlers': { 
				postName: 'togo:growlers', 
				postSuccess: function(name,value) {
					$('#place_growlers_icon').removeClass('ui-icon-check ui-icon-closethick ui-icon-help');
					if (value==true)
						$('#place_growlers_icon').addClass('ui-icon-check');
					else if (value==false)
						$('#place_growlers_icon').addClass('ui-icon-closethick');
					else
						$('#place_growlers_icon').addClass('ui-icon-help');
				}
			},
			'place_kegs': { 
				postName: 'togo:kegs',
				postSuccess: function(name,value) {
					$('#place_kegs_icon').removeClass('ui-icon-check ui-icon-closethick ui-icon-help');
					if (value==true)
						$('#place_kegs_icon').addClass('ui-icon-check');
					else if (value==false)
						$('#place_kegs_icon').addClass('ui-icon-closethick');
					else
						$('#place_kegs_icon').addClass('ui-icon-help');
				}
			},
			'place_address_street': {postName: 'address:street'},
			'place_address_city': {postName: 'address:city'},
			'place_address_state': {postName: 'address:state'},
			'place_address_zip': {postName: 'address:zip'},
			'place_address_country': {postName: 'address:country'},
		}
	});
	
	// TODO: make this work after an address has changed
	// 'afterSave': function() {
	// 	geocodeAddress(updateLatLon);
	// }
	
	$('#beerlist input[type=checkbox]').change(beerlist_edit);
	
	// TODO: bring this back, we're using the JQuery UI Autocomplete instead of this one
	// $('#beerlist_new_brewery').autocomplete('/api/autocomplete.fcgi',{
	// 	"mustMatch": true,
	// 	"extraParams": {
	// 		"dataset": "breweries"
	// 	}
	// }).result(function(evt,data,formatted) {
	// 	brewery_beerlist=[];
	// 	brewery_beerlist_ids=[];
	// 
	// 	$('#beerlist_new_beer').flushCache();
	// 	
	// 	$.getJSON('/api/search',{
	// 		"q": jQuery.isArray(data)?data[0]:data,
	// 		"dataset": "brewery"
	// 	},
	// 	function (data,status) {
	// 		if (data.response.docs.length) {
	// 			var brewery_id=data.response.docs[0].id;
	// 			$.getJSON('/api/brewery/'+brewery_id.replace(/^brewery:/,'')+'/beerlist',function(data,status){
	// 				$(data.beers).each(function(i,v){
	// 					brewery_beerlist.push(v.name);
	// 					brewery_beerlist_ids[v.name]=v.beer_id;
	// 				});
	// 				$('#beerlist_new_beer').autocomplete(brewery_beerlist).result(function(evt,data,formatted) {
	// 					beerlist_new_beer_id=brewery_beerlist_ids[data];
	// 				});
	// 			});
	// 		}
	// 	});
	// }
	// );

	$('#review_form').submit(function(){
		$.post($('#review_form').attr('action'),
		$('#review_form').serialize(),
		function(data,status){
			
		});
		return false;
	});

	var place_is_bookmarked=false;
	var user_id=get_user_id();
	if (user_id!=null) { // I'm logged in
		// Get my bookmarks to see if this place is on it
		$.getJSON('/api/bookmarks/'+user_id,function(data){
			if (data.items[$('#place_id').val()]) {
				place_is_bookmarked=true;
				$('#bookmark_link').html('Unbookmark this Place');
			}
		});
	}
	$('#bookmark_link').click(function(){
		if (place_is_bookmarked) {
			$.post('/api/bookmarks',{'del_item': $('#place_id').val(),},
			function(data) {
				// TODO: confirm to user
			});
		}
		else {
			$.post('/api/bookmarks',{'add_item': $('#place_id').val(),},
			function(data) {
				// TODO: confirm to user
			});
		}

		return false;
	});
	
	$('#rateplace_link').click(function(){
		$.getScript('/js/jquery.ui.stars.js',function() {
			$('#rating-wrapper').stars();
			$('#service-wrapper').stars();
			$('#atmosphere-wrapper').stars();
			$('#food-wrapper').stars();
			
			$('#review_form input[name=place_id]').val($('#place_id').val());
			
			$('#review_page').dialog({
				title: "Add your review",
				modal:true,
				resizable: false,
				open: function() {

					// If there's an existing review, fill out the fields
					if (get_user_id()!=null && $('#place_id').val().trim().length) {
						$.getJSON('/api/review/'+$('#place_id').val().replace(/:/g,'/')+'/'+get_user_id(),
							null,
							function(data){
								console.log(data);
								$('#rating-wrapper').stars('select',data.rating);
								$('#service-wrapper').stars('select',data.atmosphere);
								$('#atmosphere-wrapper').stars('select',data.atmosphere);
								$('#food-wrapper').stars('select',data.food);
								$('#review_form textarea[name=comments]').val(data.comments);
							}
						);
					}

					$('#post_review_button').click(function(){
						$.ajax({
							url: '/api/place/review',
							type: 'POST',
							data: $('#review_form').serialize(),
							dataType: 'json',
							success: function(data,textStatus,xhr) {
								$('#review_page').dialog('close');
							},
							error: function(xhr,textStatus,err) {
								// TODO: error-handling
							}
						})
					});
				}
			});
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

	$.getJSON('/api/'+$('#place_id').val().replace(/:/g,'/')+'/personalization',null,function(data){
		$('#predrating').parent('div').attr('title','Predicted rating for you: '+data.predictedrating+' out of 5');
		$('#predrating').css('width',data.predictedrating/5*100+'%');
	});

	

}

</script>
<?
include("../footer.php");
?>
