<?php
require_once('beercrush/beercrush.php');

$oak=new OAK;

$brewery_id=preg_replace('/:[^:]*$/','',$_GET['beer_id']);
$api_beer_id=str_replace(':','/',$_GET['beer_id']);

// $history   =BeerCrush::api_doc($oak,'beer/'.$api_beer_id.'/history');
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
<div id="main">

<div>
	<h2><a id="brewery_link" href="/brewery/<?=preg_replace('/^.*:/','',$brewerydoc->id)?>"><?=$brewerydoc->name?>'s</a></h2>
	<h1 id="beer_name"><?=$beerdoc->name?></h1>

	<div id="beer">
		<input type="hidden" id="beer_id" value="<?=$beerdoc->id?>" />
		<div><span class="label">Brewer's description:</span>
		<p id="beer_description" class="editable_textarea"><?=$beerdoc->description?></p></div>
	
		<div class="cf"><div class="label">Original Gravity: </div><div id="beer_og"><?=$beerdoc->og?></div></div>
		<div class="cf"><div class="label">Final Gravity: </div><div id="beer_fg"><?=$beerdoc->fg?></div></div>
		<div class="cf"><div class="label">Alcohol (abv): </div><div id="beer_abv"><?=$beerdoc->abv?></div></div>
		<div class="cf"><div class="label">Bitterness (IBUs): </div><div id="beer_ibu"><?=$beerdoc->ibu?></div></div>
		<div class="cf"><div class="label">Hops: </div><div id="beer_hops"><?=$beerdoc->hops?></div></div>
		<div class="cf"><div class="label">Grains: </div><div id="beer_grains"><?=$beerdoc->grains?></div></div>
		<div class="cf"><div class="label">Yeast: </div><div id="beer_yeast"><?=$beerdoc->yeast?></div></div>
		
		<div id="editable_save_msg"></div>
		<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
		<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />
	</div>

</div>
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
	</div>
	<div>Price: $<?=$review->purchase_price?> at <a href="/<?=str_replace(':','/',$review->purchase_place_id)?>"><?=$places[$review->purchase_place_id]->name?></a></div>
	<div>Poured: <?=$review->poured_from?></div>
	<div>Comments: <?=$review->comments?></div>
</div>
<?php endforeach; ?>
<!-- <div id="reviewdata"></div> -->

<h3>Post a review</h3>
<form id="review_form">
	<input type="hidden" name="beer_id" value="<?=$beerdoc->id?>" />
	<div class="cf"><div class="label">Rating:</div><div> 
		<input name="rating" type="radio" value="1" />1
		<input name="rating" type="radio" value="2" />2
		<input name="rating" type="radio" value="3" />3
		<input name="rating" type="radio" value="4" />4
		<input name="rating" type="radio" value="5" />5
	</div></div>
	<div class="cf"><div class="label">Body:</div><div> 
		<input name="body" type="radio" value="1" />1
		<input name="body" type="radio" value="2" />2
		<input name="body" type="radio" value="3" />3
		<input name="body" type="radio" value="4" />4
		<input name="body" type="radio" value="5" />5
	</div></div>
	<div class="cf"><div class="label">Balance:</div><div> 
		<input name="balance" type="radio" value="1" />1
		<input name="balance" type="radio" value="2" />2
		<input name="balance" type="radio" value="3" />3
		<input name="balance" type="radio" value="4" />4
		<input name="balance" type="radio" value="5" />5
	</div></div>
	<div class="cf"><div class="label">Aftertaste:</div><div> 
		<input name="aftertaste" type="radio" value="1" />1
		<input name="aftertaste" type="radio" value="2" />2
		<input name="aftertaste" type="radio" value="3" />3
		<input name="aftertaste" type="radio" value="4" />4
		<input name="aftertaste" type="radio" value="5" />5
	</div></div>
	<div>
		Price: <input name="purchase_price" type="text" size="10" />
		at <input name="purchase_place_name" type="text" size="40" />
		<input type="hidden" id="purchase_place_name_id" name="purchase_place_id" />
	</div>
	<div>
		Flavors: WARNING NOT ALL FLAVORS ARE CURRENTLY SHOWING, NEED TO DISCUSS
		<table id="flavors_table"><?=output_flavors($flavors->flavors)?></table>
		<input type="hidden" name="flavors" value="" />
	</div>
	<div>
		<p>Comments:</p>
		<textarea name="comments" rows="5" cols="80"></textarea>
	</div>
	
	<input id="post_review_button" type="button" value="Post my review" />
	<div id="review_result_msg"></div>
</form>
</div>
<div id="sidebar">
<h3>Photos</h3>

<?php foreach ($photoset->photos as $photo) :?>
	<div class="photos">
	<img src="<?=$photo->url?>?size=small" />
	<a href="/user/<?=$photo->user_id?>"><?=$users[$photo->user_id]->name?></a> <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$photo->timestamp)?></span>
	</div>
<?php endforeach; ?>

<div id="new_photos"></div>

<input id="photo_upload" name="photo" type="file" />

<p></p>
<h3>Beer Edit History</h3>
<div>Beer last modified: <span id="beer_lastmodified" class="datestring"><?=date('D, d M Y H:i:s O',$beerdoc->meta->mtime)?></span></div>
<div id="history"></div>

<?php
if ($history) {
	foreach ($history->changes as $change) {
?>
	<div><?=$change->index?></div>
<?php
		$oldprops=get_object_vars($change->change->old);
		if ($oldprops===false || is_null($oldprops)) // PHP 5.3 returns null, pre-5.3 returns false
			$oldprops=array();
		else
			$oldprops=array_keys($oldprops);
		$newprops=get_object_vars($change->change->new);
		if ($newprops===false || is_null($newprops))
			$newprops=array();
		else
			$newprops=array_keys($newprops);
		$allprops=array_merge($oldprops,$newprops);
		foreach ($allprops as $prop) {
			if (isset($change->change->old->$prop) && isset($change->change->new->$prop)) { // Changed
				print '<div>'.$prop.':'.$change->change->old->$prop.' -> '.$change->change->new->$prop.'</div>';
			}
			else if (isset($change->change->old->$prop)) { // Deleted value
				print '<div>'.$prop.':'.$change->change->old->$prop.' -> </div>';
			}
			else { // Added value
				print '<div>'.$prop.':->'.$change->change->new->$prop.'</div>';
			}
		}
	}
}
?>

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

var dochistory=new Array();
var docversions=new Object();

function mydiff(a,b) {
	var diffs=new Object();

	// Get the union of properties of a & b
	var allprops=new Array();
	for (var p in a) {allprops.push(p);}
	for (var p in b) {if (jQuery.inArray(p,allprops) == -1) allprops.push(p);}
	
	for (var i in allprops) {
		var p=allprops[i];

		if (typeof(a[p])!==typeof(b[p])) { // Different types
			if (typeof(diffs[p])=='undefined')
				diffs[p]=new Object();
				
			if (typeof(a[p])=='undefined') {
				diffs[p].old=null;
				diffs[p].new=b[p];
			}
			else if (typeof(b[p])=='undefined') {
				diffs[p].old=a[p];
				diffs[p].new=null;
			}
			else {
				diffs[p].old=a[p];
				diffs[p].new=b[p];
			}
		}
		else if (a[p] != b[p]) { // Same type, different values
			if (typeof(diffs[p])=='undefined')
				diffs[p]=new Object();
				
			if (typeof(a[p])=='object') {
				jQuery.extend(diffs[p],mydiff(a[p],b[p]));
			}
			else if (typeof(a[p])=='array') {
				for (var arrayidx in a[p]) {
					if (a[p][arrayidx]!==b[p][arrayidx]) {
						diffs[p][arrayidx]=new Object();
						jQuery.extend(diffs[p],mydiff(a[p][arrayidx],b[p][arrayidx]));
					}
				}
			}
			else {
				diffs[p].old=a[p];
				diffs[p].new=b[p];
			}
		}
	}

	return diffs;
}

var exclude_props=[ '_rev', 'meta' ];

function display_diffs(vindex,diffs) {
	for (var prop in diffs) {
		if (jQuery.inArray(prop,exclude_props)==-1) {
			if (typeof(diffs[prop].old) == 'undefined' && typeof(diffs[prop].new) == 'undefined') {
				display_diffs_base(vindex,diffs[prop]);
			}
			else {
				var s;
				if (diffs[prop].old!=null && diffs[prop].old.length) {
					s='Changed '+prop+' from <span class="version_change_from">'+diffs[prop].old+'</span> to <span class="version_change_to">'+diffs[prop].new+'</span>';
				}
				else {
					s='Added '+prop+': <span class="version_change_new">'+diffs[prop].new+'</span>';
				}
				s='<div>'+s+'</div>';
				$('#version_'+vindex).append(s);
			}
		}
	}
	
}

function show_diff(a,b) {
	// console.log(a);
	// console.log(b);
	var path=$('#beer_id').val().replace(/:/g,'/');

	var doca=null;
	var docb=null;
	
	$.getJSON('/api/history/'+path+'/'+a,function(doc){
		doca=doc;
		if (doca && docb) {
			var diffs=mydiff(doca,docb);
			display_diffs(b,diffs);
		}
	});
	$.getJSON('/api/history/'+path+'/'+b,function(doc){
		docb=doc;
		if (doca && docb) {
			var diffs=mydiff(doca,docb);
			display_diffs(b,diffs);
		}
	});
}

function show_history() {
	var path=$('#beer_id').val().replace(/:/g,'/');
	$.getJSON('/api/history/'+path,function(data){
		for (var i=0,j=data.changes.length;i<j;++i) {
			dochistory.push(data.changes[i].index);
			$('#history').append('<div id="version_'+data.changes[i].index+'">'+data.changes[i].date+'</div>');
			if ((i+1) < j) {
				$('#version_'+data.changes[i].index).append('<a href="#" onclick="show_diff(\''+data.changes[i+1].index+'\',\''+data.changes[i].index+'\');return false;">Show Diff</a>');
			}
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
	
	show_history();
	
}

</script>

<?php include("../footer.php"); ?>
