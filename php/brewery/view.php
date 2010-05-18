<?php
require_once('beercrush/beercrush.php');

$brewerydoc=$BC->docobj("/brewery/".$_GET['brewery_id']);
$beerlistdoc=$BC->docobj("/brewery/".$_GET['brewery_id']."/beerlist");
if ($beerlistdoc==null)
{
	$beerlistdoc->beers=array();
}
$photoset=$BC->docobj('photoset/brewery/'.$_GET['brewery_id']);	

$header['title']=$brewerydoc->name;
$header['js'][]='<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';
$header['js'][]='<script type="text/javascript" src="/js/jquery.uploadify.v2.1.0.js"></script>';
$header['js'][]='<script type="text/javascript" src="/js/swfobject.js"></script>';

include("../header.php");
?>
<div id="main">

<div id="mainwithright">

<div id="brewery">
	<input type="hidden" id="brewery_id" value="<?=$brewerydoc->id?>">
	<h1 id="brewery_name"><?=$brewerydoc->name?></h1>

	<span id="address">
		<div class="cl"><div class="label">Street:</div><div id="brewery_address:street"><?=$brewerydoc->address->street?></div></div>
		<div class="cl"><div class="label">City:</div><div id="brewery_address:city"><?=$brewerydoc->address->city?></div></div>
		<div class="cl"><div class="label">State:</div><div id="brewery_address:state"><?=$brewerydoc->address->state?></div></div>
		<div class="cl"><div class="label">Zip:</div><div id="brewery_address:zip"><?=$brewerydoc->address->zip?></div></div>
		<div class="cl"><div class="label">Country:</div><div id="brewery_address:country"><?=$brewerydoc->address->country?></div></div>
	</span>

	<div class="cl"><div class="label">Phone:</div><div id="brewery_phone"><?=$brewerydoc->phone?></div></div>
	<div class="cl"><div class="label">Web site:</div><div><span id="brewery_uri"><?=$brewerydoc->uri?></span> <span><a href="<?=$brewerydoc->uri?>">Visit web site</a></span></div></div>
	<div class="cl"><div class="label">About us:</div><div><span id="brewery_description"><?=$brewerydoc->description?></span></div></div>

	<div id="editable_save_msg"></div>
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />

</div>
</div><!--weird extra div required, not sure i get it-->


<h2>[Count] Beers Brewed</h2>
<ul id="beerlist">
<?php foreach ($beerlistdoc->beers as $beer){ ?>
	<li><a href="/<?=str_replace(':','/',$beer->beer_id)?>"><?=$beer->name?></a></li>
<?php } ?>
</ul>

<h3>Missing Beer?  Add it</h3>
<form id="new_beer_form" method="post" action="/api/beer/edit">
	<input type="hidden" name="brewery_id" value="<?=$brewerydoc->id?>" />
	<input type="text" size="30" name="name" value="" />
	<input type="submit" value="Add Beer" /><div id="new_beer_msg" class="hidden"></div></form>
<div class="help">
	<p>We already know the brewery, so just type the beer name, e.g. "Pale Ale" rather than "Sierra Nevada Pale Ale."  Go to the beer page to update that beer's data.</p>
</div>

</div>
<div id="rightcol">
	<div id="map"></div>
	<h3>Visiting</h3>
	<div class="cl"><div class="label">Hours:</div><div id="brewery_hours"><?=$brewerydoc->hours?></div></div>
	<div class="cl"><div class="label">Tasting:</div><div id="brewery_tasting"><?=$brewerydoc->tasting?></div></div>
	<div class="cl"><div class="label">Tours:</div><div id="brewery_tours"><?=$brewerydoc->tours?></div></div>
	<div class="cl"><div class="label">Bottles to go:</div><div id="brewery_bottles"><?=$brewerydoc->bottles?></div></div>
	<div class="cl"><div class="label">Growlers to go:</div><div id="brewery_growlers"><?=$brewerydoc->growlers?></div></div>
	<div class="cl"><div class="label">Kegs to go:</div><div id="brewery_kegs"><?=$brewerydoc->kegs?></div></div>
	
</div>

</div>
<div id="leftcol">
<?php foreach ($photoset->photos as $photo) :?>
	<div class="photo">
	<img src="<?=$photo->url?>?size=small" />
	<p class="caption"><a href="/user/<?=$photo->user_id?>"><?=$BC->docobj('user/'.$photo->user_id)->name?></a> <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$photo->timestamp)?></span></p>
	</div>
<?php endforeach; ?>

<div id="new_photos"></div>

<input id="photo_upload" name="photo" type="file" />
</div>
	
<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function geocodeAddress(callback) {
	var addressstr=$('#brewery_address\\:street').text() + ', ' +
		$('#brewery_address\\:city').text() + ', ' +
		$('#brewery_address\\:state').text() + ' ' +
		$('#brewery_address\\:zip').text() + ' ' +
		$('#brewery_address\\:country').text();

	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({
		address: addressstr
	},
	callback);
    
}

var brewery_latitude=<?=$brewerydoc->address->latitude?$brewerydoc->address->latitude:'null'?>;
var brewery_longitude=<?=$brewerydoc->address->longitude?$brewerydoc->address->longitude:'null'?>;

function makemap(lat,lon) {
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
		title: "<?=$brewery->name?>"
	});
	
}

function updateLatLon(results,status) {
	if (status == google.maps.GeocoderStatus.OK) {
		brewery_latitude=results[0].geometry.location.lat();
		brewery_longitude=results[0].geometry.location.lng();
		makemap(brewery_latitude,brewery_longitude);

		$.post('/api/brewery/edit',{
			'brewery_id': $('#brewery_id').val(),
			'address:latitude': brewery_latitude,
			'address:longitude': brewery_longitude
		},function(data){
		});
	}
}

function pageMain()
{
	if (brewery_longitude && brewery_latitude) {
		makemap(brewery_latitude,brewery_longitude)
	}
	else {
		geocodeAddress(updateLatLon);
	}
    
	makeDocEditable('#brewery','brewery_id','/api/brewery/edit',{
		'afterSave': function() {
			geocodeAddress(updateLatLon);
		}
	});
	
	$('#new_beer_form').submit(function() {
		$('#new_beer_msg').text('Adding...');
		$('#new_beer_form').ajaxError(function(e,xhr,options,exception) {
			if (options.url=='/api/beer/edit') {
				if (xhr.status==409) { // Duplicate beer
					$('#new_beer_msg').removeClass('hidden feedback_success');
					$('#new_beer_msg').addClass('feedback_error');
					$('#new_beer_msg').html("There's already a beer with that name.");
				}
			}
		});
		
		$.post(
			$(this).attr('action'),
			$('#new_beer_form').serialize(),
			function(data,status,xhr){
				$('#new_beer_msg').removeClass('hidden feedback_error');
				$('#new_beer_msg').addClass('feedback_success');
				$('#new_beer_msg').html('Beer added! <a href="/'+data.id.replace(/:/g,'/')+'">Add more details or rate <b>"'+data.name+'"</b></a>');
				
				$('#beerlist').append('<div><a href="/'+data.id.replace(/:/g,'/')+'">'+data.name+'</a></div>');
			},
			'json'
		);
		return false;
	});
	
	$('#photo_upload').uploadify({
		'uploader'  : '/flash/uploadify.swf',
		'script'    : '/api/brewery/photo',
		'cancelImg' : '/img/uploadify/cancel.png',
		'auto'      : true,
		'multi'     : true,
		'fileDataName': 'photo',
		'fileDesc'	: 'Upload a photo',
		'fileExt'	: '*.jpg;*.jpeg;*.png',
		'buttonText': "POST A PHOTO", 
		'sizeLimit' : 5000000, 
		'scriptData': {
			'brewery_id': $('#brewery_id').val(),
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


<?php
	include("../footer.php");
?>