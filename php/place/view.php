<?php
require_once('beercrush/beercrush.php');

$oak=new OAK(BeerCrush::CONF_FILE);
$place   =BeerCrush::api_doc($oak,'place/'.str_replace(':','/',$_GET['place_id']));
$beerlist=BeerCrush::api_doc($oak,'place/'.str_replace(':','/',$_GET['place_id']).'/menu');
$reviews =BeerCrush::api_doc($oak,'review/place/'.str_replace(':','/',$_GET['place_id']).'/0');
$photoset=BeerCrush::api_doc($oak,'photoset/place/'.$_GET['place_id']);	

if (is_null($beerlist)) {
	$beerlist=new stdClass;
	$beerlist->items=array();
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

include("../header.php");
?>

<div id="editable_save_msg"></div>
<div id="place">
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />

	<input type="hidden" value="<?=$place->id?>" id="place_id">
	<h1 id="place_name"><?=$place->name?></h1>
	<div>Type: <?=$place->placetype?></div>

	<div id="address">
		<div>Street:<span id="place_address:street"><?=$place->address->street?></span></div>
		<div>City:<span id="place_address:city"><?=$place->address->city?></span></div>
		<div>State:<span id="place_address:state"><?=$place->address->state?></span> </div>
		<div>Zip:<span id="place_address:zip"><?=$place->address->zip?></span> </div>
		<div>Country:<span id="place_address:country"><?=$place->address->country?></span></div>
		<input type="hidden" name="latitude" value="<?=$place->address->latitude?>" />
		<input type="hidden" name="longitude" value="<?=$place->address->longitude?>" />
	</div>
	
	<div>Phone:<span id="place_phone"><?=$place->phone?></span></div>
	
	<div>Web site:
		<span id="place_uri" href="<?=$place->uri?>"><?=$place->uri?></span>
		<a href="<?=$place->uri?>">Visit web site</a>
	</div>

	<div>Description:<span id="place_description"><?=$place->description?></span></div>

	<div id="map" style="width:300px;height:300px"></div>

	<h2>Details</h2>
	<div>Kid-Friendly: <?php echo isset($place->kid_friendly)?($place->kid_friendly?'Yes':'No'):'Unknown'; ?></div>
	<div>Outdoor seating: <?php echo isset($place->restaurant->outdoor_seating)?($place->restaurant->outdoor_seating?'Yes':'No'):'Unknown'; ?></div>
	<div>Wi-Fi: <?php echo isset($place->wifi)?($place->wifi?'Yes':'No'):'Unknown'; ?></div>

	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />
	
</div>

<h2><?=count($beerlist->items)?> Beers Available</h2>
<div id="beerlist">
	<table>
		<tr>
			<th>Brewery</th>
			<th>Beer</th>
			<th>Price</th>
			<th>Tap</th>
			<th>Cask</th>
			<th>Bottle (12 fl. oz.)</th>
			<th>Bottle (22 fl. oz.)</th>
			<th>Can</th>
		</tr>
	<?foreach ($beerlist->items as $item) :?>
	<tr>
		<td><a href="/<?=str_replace(':','/',$item->brewery->id)?>"><?=$item->brewery->name?></a></td>
		<td><a href="/<?=str_replace(':','/',$item->id)?>"><?=$item->name?></a> <input type="button" onclick="beerlist_delete('<?=$item->id?>',event);" value="Delete" /></td>
		<td><?=$item->price?'$'.number_format($item->price,2):''?></td>

		<td><input <?=$item->ontap     ?'checked="checked"':''?> type="checkbox" value="tap"      name="serving_<?=$item->id?>" /></td>
		<td><input <?=$item->oncask    ?'checked="checked"':''?> type="checkbox" value="cask"     name="serving_<?=$item->id?>" /></td>
		<td><input <?=$item->inbottle  ?'checked="checked"':''?> type="checkbox" value="bottle"   name="serving_<?=$item->id?>" /></td>
		<td><input <?=$item->inbottle22?'checked="checked"':''?> type="checkbox" value="bottle22" name="serving_<?=$item->id?>" /></td>
		<td><input <?=$item->incan     ?'checked="checked"':''?> type="checkbox" value="can"      name="serving_<?=$item->id?>" /></td>

	</tr>
	<?endforeach;?>

	<tr>
		<td colspan="8">Add a beer:</td>
	</tr>
	<tr>
		<td>
			<input id="beerlist_new_brewery"    type="text" size="20" name="beerlist_new_brewery" />
			<input id="beerlist_new_brewery_id" type="hidden" name="beerlist_new_brewery_id" />
		</td>
		<td>
			<input id="beerlist_new_beer"    type="text" size="20" name="beerlist_new_beer" />
			<input id="beerlist_new_beer_id" type="hidden" name="beerlist_new_beer_id" />
		</td>
		<td><input id="beerlist_new_price" type="text" size="4" name="beerlist_new_price" /></td>
		<td><input type="button" onclick="beerlist_add(event,$('#beerlist_new_brewery').val(),$('#beerlist_new_beer').val(),$('#beerlist_new_price').val());" value="Add" /></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
	</table>
</div>

<h2>Photos</h2>

<?php foreach ($photoset->photos as $photo) :?>
	<div>
	<img src="<?=$photo->url?>?size=small" />
	<a href="/user/<?=$photo->user_id?>"><?=$BC->docobj('user/'.$photo->user_id)->name?></a> <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$photo->timestamp)?></span>
	</div>
<?php endforeach; ?>

<div id="new_photos"></div>

<input id="photo_upload" name="photo" type="file" />

<h2><?=count($reviews->reviews)?> Reviews</h2>
<div id="reviewlist">
	<?foreach($reviews->reviews as $review):?>
	<div>
		<div><img src="<?=$BC->docobj('user/'.$review->user_id)->avatar?>" /><a href="/user/<?=$review->user_id?>"><?=$BC->docobj('user/'.$review->user_id)->name?></a></div>
		<div>Rating: <?=str_repeat("&#9829;",$review->rating)?></div>
		<div>Kid-Friendly: <?=str_repeat("&#9829;",$review->kidfriendly)?></div>
		<div><?=$review->comments?></div>
	</div>
	<?endforeach?>
</div>

<h3>Add your review</h3>
<form id="review_form" method="post" action="/api/place/review">
	<input type="hidden" name="place_id" value="<?=$place->id?>">
	<div>Rating:
		(Hated)<input type="radio" name="rating" value="1" />1 
		<input type="radio" name="rating" value="2" />2
		<input type="radio" name="rating" value="3" />3
		<input type="radio" name="rating" value="4" />4
		<input type="radio" name="rating" value="5" />5 (Loved)
	</div>
	<div>Kid-Friendly-ness:
		
		(Don't take kids here)<input type="radio" name="kidfriendly" value="1" />1 
		<input type="radio" name="kidfriendly" value="2" />2
		<input type="radio" name="kidfriendly" value="3" />3
		<input type="radio" name="kidfriendly" value="4" />4
		<input type="radio" name="kidfriendly" value="5" />5 (Your kids will love it)
	</div>
	<div>Comments:</div>
	<div><textarea name="comments" rows="5" cols="60"></textarea></div>
	
	<input type="submit" value="Post" />
</form>

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
		$(evt.target).parents().filter('tr').first().remove();
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

function pageMain()
{
	var lat=$('#address input[type="hidden"][name="latitude"]').val();
	var lon=$('#address input[type="hidden"][name="longitude"]').val();
	if (lat && lon) {
		var latlng = new google.maps.LatLng(lat,lon);
		var myOptions = {
			zoom: 10,
			center: latlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
		};
	    var map = new google.maps.Map(document.getElementById("map"), myOptions);
		var marker=new google.maps.Marker({
			position: latlng,
			map: map,
			title: "<?=$place->name?>"
		});
	}
    

	makeDocEditable('#place','place_id','/api/place/edit');
	
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
		'fileDesc'	: 'Upload a photo',
		'fileExt'	: '*.jpg;*.jpeg;*.png',
		'buttonText': "Upload a photo", 
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