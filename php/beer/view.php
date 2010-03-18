<?php
require_once('beercrush/beercrush.php');

$oak=new OAK;

$brewery_id=preg_replace('/:[^:]*$/','',$_GET['beer_id']);
$api_beer_id=str_replace(':','/',$_GET['beer_id']);

$beerdoc   =BeerCrush::api_doc($oak,'beer/'.$api_beer_id);
$brewerydoc=BeerCrush::api_doc($oak,'brewery/'.$brewery_id);
$reviews   =BeerCrush::api_doc($oak,'review/beer/'.$api_beer_id.'/0');
$flavors   =BeerCrush::api_doc($oak,'flavors');
$photoset  =BeerCrush::api_doc($oak,'photoset/beer/'.$api_beer_id);	

// Build flavor id lookup table
$flavor_lookup=array();
build_flavor_lookup_table($flavors->flavors);

$places=array();
$users=array();
foreach ($reviews->reviews as $review)
{
	if (!isset($places[$review->purchase_place_id])) {
		$places[$review->purchase_place_id]=BeerCrush::api_doc($oak,str_replace(':','/',$review->purchase_place_id));
	}
	if (!isset($users[$review->user_id])) {
		$users[$review->user_id]=BeerCrush::api_doc($oak,'user/'.$review->user_id);
	}
}

foreach ($photoset->photos as $photo) {
	if (!isset($users[$photo->user_id])) {
		$users[$photo->user_id]=BeerCrush::api_doc($oak,'user/'.$photo->user_id);
	}
}

function build_flavor_lookup_table($flavors)
{
	global $flavor_lookup;
	
	foreach ($flavors as $flavor)
	{
		if (isset($flavor->flavors))
		{
			build_flavor_lookup_table($flavor->flavors);
		}
		else
		{
			$flavor_lookup[$flavor->id]=$flavor->title;
		}
	}
}

function output_flavors($flavors)
{
	foreach ($flavors as $flavor)
	{
		if (isset($flavor->flavors))
		{
			print '<tr><td>'.$flavor->title.':</td>';
			output_flavors($flavor->flavors);
			print '</tr>';
		}
		else
		{
			print '<td><input type="checkbox" name="flavors_set[]" value="'.$flavor->id.'" />'.$flavor->title.'</td>';
		}
	}
}


// Add the CSS for Uploadify
$header['css'][]='<link href="/css/uploadify.css" rel="stylesheet" type="text/css" />';
$header['title']=$brewerydoc->name.' '.$beerdoc->name;

include("../header.php");
?>

<a id="brewery_link" href="/brewery/<?=preg_replace('/^.*:/','',$brewerydoc->id)?>"><?=$brewerydoc->name?></a>

<div id="beer">
	<h2 id="beer_name"><?=$beerdoc->name?></h2>

	<input type="hidden" id="beer_id" value="<?=$beerdoc->id?>" />
	<div id="beer_description" class="editable_textarea"><?=$beerdoc->description?></div>

	<div>OG: <span id="beer_og"><?=$beerdoc->og?></span></div>
	<div>FG: <span id="beer_fg"><?=$beerdoc->fg?></span></div>
	<div>ABV: <span id="beer_abv"><?=$beerdoc->abv?>%</span></div>
	<div>IBU: <span id="beer_ibu"><?=$beerdoc->ibu?></span></div>
	<div>Hops: <span id="beer_hops"><?=$beerdoc->hops?></span></div>
	<div>Grains:<span id="beer_grains"><?=$beerdoc->grains?></span></div>
	<div>Yeast:<span id="beer_yeast"><?=$beerdoc->yeast?></span></div>
	
	<div id="editable_save_msg"></div>
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />
	
</div>
<div>Beer last modified: <span id="beer_lastmodified" class="datestring"><?=date('D, d M Y H:i:s O',$beerdoc->meta->mtime)?></span></div>

<h3>Photos</h3>

<?php foreach ($photoset->photos as $photo) :?>
	<div>
	<img src="<?=$photo->url?>?size=small" />
	<a href="/user/<?=$photo->user_id?>"><?=$users[$photo->user_id]->name?></a> <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$photo->timestamp)?></span>
	</div>
<?php endforeach; ?>

<div id="new_photos"></div>

<input id="photo_upload" name="photo" type="file" />

<h3><?=count($reviews->reviews)?> Reviews</h3>

<?php foreach ($reviews->reviews as $review) :?>
<div>
	<div>User: <a href="/user/<?=$review->user_id?>"><?=$users[$review->user_id]->name?></a></div>
	<div>Posted: <span class="datestring"><?=date('D, d M Y H:i:s O',$review->meta->timestamp)?></span></div>
	<div>Date Drank: <span class="datestring"><?=!empty($review->date_drank)?date('D, d M Y H:i:s O',strtotime($review->date_drank)):''?></span></div>
	<div>Rating: <?=$review->rating?></div>
	<div>Body: <?=$review->body?></div>
	<div>Balance: <?=$review->balance?></div>
	<div>Aftertaste: <?=$review->aftertaste?></div>
	<div>Flavors: <?php
		$flavor_titles=array();
		if (isset($review->flavors))
		{
			foreach ($review->flavors as $flavor){$flavor_titles[]=$flavor_lookup[$flavor];}
		}
		print join(', ',$flavor_titles);
	?>
	<div>Price: $<?=$review->purchase_price?> at <a href="/<?=str_replace(':','/',$review->purchase_place_id)?>"><?=$places[$review->purchase_place_id]->name?></a></div>
	<div>Poured: <?=$review->poured_from?></div>
	<div>Comments: <?=$review->comments?></div>
</div>
<?php endforeach; ?>

<h3>Post a review</h3>
<form id="review_form">
	<input type="hidden" name="beer_id" value="<?=$beerdoc->id?>" />
	<div>
		Rating: 
		<input name="rating" type="radio" value="1" />1
		<input name="rating" type="radio" value="2" />2
		<input name="rating" type="radio" value="3" />3
		<input name="rating" type="radio" value="4" />4
		<input name="rating" type="radio" value="5" />5
	</div>
	<div>
		Body: 
		<input name="body" type="radio" value="1" />1
		<input name="body" type="radio" value="2" />2
		<input name="body" type="radio" value="3" />3
		<input name="body" type="radio" value="4" />4
		<input name="body" type="radio" value="5" />5
	</div>
	<div>
		Balance: 
		<input name="balance" type="radio" value="1" />1
		<input name="balance" type="radio" value="2" />2
		<input name="balance" type="radio" value="3" />3
		<input name="balance" type="radio" value="4" />4
		<input name="balance" type="radio" value="5" />5
	</div>
	<div>
		Aftertaste: 
		<input name="aftertaste" type="radio" value="1" />1
		<input name="aftertaste" type="radio" value="2" />2
		<input name="aftertaste" type="radio" value="3" />3
		<input name="aftertaste" type="radio" value="4" />4
		<input name="aftertaste" type="radio" value="5" />5
	</div>
	<div>
		Price: <input name="purchase_price" type="text" size="10" />
		at <input name="purchase_place_name" type="text" size="40" />
		<input type="hidden" id="purchase_place_name_id" name="purchase_place_id" />
	</div>
	<div>
		Flavors: <table id="flavors_table"><?=output_flavors($flavors->flavors)?></table>
		<input type="hidden" name="flavors" value="" />
	</div>
	<div>
		Comments:
		<textarea name="comments" rows="5" cols="80"></textarea>
	</div>
	
	<input id="post_review_button" type="button" value="Post my review" />
	<div id="review_result_msg"></div>
</form>

<!-- <div id="reviewdata"></div> -->

<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript" src="/js/jquery.uploadify.v2.1.0.js"></script>
<script type="text/javascript" src="/js/swfobject.js"></script>
<script type="text/javascript">

function undo_photo(uuid,url) {
	console.log(url);
	$.ajax({
		"url": url,
		"type": "DELETE",
		"error": function (xhr,status,err) {
			console.log('DELETE failed:');
			console.log(status);
			console.log(xhr);
			console.log(err);
		},
		"success": function (data,status,xhr) {
			console.log('removing div #new_photo-'+uuid);
			$('#new_photo-'+uuid).remove();
		}
	});
}

function pageMain()
{
	$('#post_review_button').click(function(){
		// $('#reviewdata').text($('#review_form').serialize());
		$('#review_result_msg').text(); // Clear last message, if any
		$(this).ajaxError(function(evt,xhr,options,err) {
			if (options.url=='/api/beer/review') {
				doc=$.parseJSON(xhr.responseText);
				$('#review_result_msg').text(doc.exception.message);
			}
		});
		flavors=new Array();
		$("#review_form input[name='flavors_set[]']:checked").each(function(idx,elem) {
			flavors[flavors.length]=$(elem).val();
		});
		$("#review_form input[name='flavors']").val(flavors.join(' '));
		$.post('/api/beer/review',$('#review_form').serialize(),function(data){
			$('#reviewdata').text(data)
		});
	});
	
	// Make the beer doc editable
	makeDocEditable('#beer','beer_id','/api/beer/edit');
	
	$('#photo_upload').uploadify({
		'uploader'  : '/flash/uploadify.swf',
		'script'    : '/api/beer/photo',
		'cancelImg' : '/img/uploadify/cancel.png',
		'auto'      : true,
		'multi'     : true,
		'fileDataName': 'photo',
		'fileDesc'	: 'Upload a photo',
		'fileExt'	: '*.jpg;*.jpeg;*.png',
		'buttonText': "Upload a photo", 
		'sizeLimit' : 5000000, 
		'scriptData': {
			'beer_id': $('#beer_id').val(),
			'userid': $.cookie('userid')
		},
		'onComplete': function(evt,queueID,fileObj,response,data) {
			photoinfo=$.parseJSON(response);
			$('#new_photos').append('<img src="'+photoinfo.url+'?size=small" />');
			return true;
		}
	});
	
	$("#review_form input[name='purchase_place_name']").autocomplete('/api/autocomplete.fcgi',{
		"mustMatch": true,
		"extraParams": {
			"dataset": "places"
		}
	}).result(function(evt,data,formatted) {
		if (data) {
			$.getJSON('/api/search?q='+data+'&dataset=place',function(data,textStatus){
				$("#purchase_place_name_id").val(data.response.docs[0].id);
			})
		}
		else {
			$("#purchase_place_name_id").val('');
		}
	});
}

</script>

<?php include("../footer.php"); ?>
