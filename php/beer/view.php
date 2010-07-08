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
$styles    =BeerCrush::api_doc($oak,'beerstyles');
$colors    =BeerCrush::api_doc($oak,'beercolors');
$photoset  =BeerCrush::api_doc($oak,'photoset/beer/'.$api_beer_id);	
$recommends=BeerCrush::api_doc($oak,'recommend/beer/'.$api_beer_id);	
$beerlist  =BeerCrush::api_doc($oak,'brewery/'.$brewery_id.'/beerlist');

if (empty($beerdoc->styles) || empty($beerdoc->styles[0]))
	$beerdoc->styles=array();
else {
	// Get a list of random beers in this beer's (first) style
	$solr_url='http://'.$BC->oak->get_config_info()->solr->nodes[rand()%count($BC->oak->get_config_info()->solr->nodes)].$BC->oak->get_config_info()->solr->url.'/select?';
	$url=$solr_url.'fl=id&start=0&rows=0&wt=json&q=style:'.$beerdoc->styles[0];
	$response=json_decode(@file_get_contents($url));

	// Pick the 1st random number
	$initial=rand(0,$response->response->numFound);
	$random_picks=array($initial);
	// Now, pick at most 3 more numbers within 100 of that (careful not to go out of bounds)
	for ($i=0,$j=min(3,$response->response->numFound);$i<$j;++$i) {
		$random_picks[]=rand(max(0,$initial-50),min($response->response->numFound,$initial+50));
	}

	// Do a 2nd query to get those
	$start=min($random_picks);
	$url=$solr_url.'fl=id&start='.$start.'&rows=100&wt=json&q=style:'.$beerdoc->styles[0];
	$response=json_decode(@file_get_contents($url));

	$other_by_style=array();
	foreach ($random_picks as $pick) {
		$other_by_style[]=$response->response->docs[$pick-$start]->id;
	}
}

$styles_lookup=array();
build_style_lookup_table($styles->styles);

$color=get_color_rgb($beerdoc->srm);

$color_titles=array();
foreach ($colors->colors as $c) {
	$color_titles[$c->srm]=$c->name;
}

$availability_titles=array(
	'year-round' => "Year-round",
	'spring' 	 => "Spring",
	'summer' 	 => "Summer",
	'fall' 		 => "Fall",
	'winter' 	 => "Winter",
	'seasonal' 	 => "Seasonal",
	'limited' => "Limited",
);
	
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

if (isset($recommends->beer)) {
	foreach ($recommends->beer as &$rec_beer) {
		$rec_beer=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($rec_beer));
		$rec_beer->brewery=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($rec_beer->brewery_id));
		// print_r($rec_beer);exit;
	}
}

if (isset($recommends->similar)) {
	foreach ($recommends->similar as &$rec_beer) {
		$rec_beer=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($rec_beer));
		$rec_beer->brewery=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($rec_beer->brewery_id));
		// print_r($rec_beer);exit;
	}
}

function build_style_lookup_table($styles) {
	global $styles_lookup;
	
	foreach ($styles as $style) {
		$styles_lookup[$style->id]=$style;//->name;
		if (isset($style->styles))
			build_style_lookup_table($style->styles);
	}
}

function get_color_rgb($srm) {
	global $colors;
	foreach ($colors->colors as $color) {
		if ($color->srmmin <= $srm && ($srm <= $color->srmmax || empty($color->srmmax))) { // The last one doesn't have an srmmax, hence the check for empty()
			return $color;
		}
	}
	return null;
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

// Add the CSS for Uploadify
$header['css'][]='<link href="/css/uploadify.css" rel="stylesheet" type="text/css" />';
$header['css'][]='<link href="/css/jquery.ui.stars.css" rel="stylesheet" type="text/css" />';
$header['title']=$brewerydoc->name.' '.$beerdoc->name;
$header['js'][]='<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';
$header['js'][]='<script type="text/javascript" src="/js/jquery.uploadify.v2.1.0.js"></script>';
$header['js'][]='<script type="text/javascript" src="/js/swfobject.js"></script>';

include("../header.php");
?>
<div id="mwr">
	
<div id="main">
	<h2><a id="brewery_link" href="/<?=BeerCrush::docid_to_docurl($brewerydoc->id)?>"><?=$brewerydoc->name?>'s</a></h2>
	<h1><?=$beerdoc->name?></h1>
	<a href="/style/<?=$styles_lookup[$beerdoc->styles[0]]->id?>"><?=$styles_lookup[$beerdoc->styles[0]]->name?></a>
	<div id="ratings_section" class="cf">
		<div class="star_rating" title="Average rating: <?=$beerdoc->review_summary->avg?> out of 5"><div id="avgrating" style="width: <?=$beerdoc->review_summary->avg/5*100?>%"></div></div>
		<div class="star_rating" title=""><div id="predrating" style="width: 0%"></div></div>
		<a href="#ratings" id="ratingcount"><?=count($reviews->reviews)?> ratings</a>
		<div class="flavors">
			<?php foreach ($beerdoc->review_summary->flavors as $f):?>
				<span class="size3"><?=$flavor_lookup[$f]?> </span>
			<?php endforeach?>
		</div>
		<p>Body</p>
		<div class="body"><div class="meter"><div style="width: <?=$beerdoc->review_summary->body_avg/5*100?>%"></div></div></div>
		<p>Balance</p>
		<div class="balance"><div class="meter"><div style="width: <?=$beerdoc->review_summary->balance_avg/5*100?>%"></div></div></div>
		<p>Aftertaste</p>
		<div class="aftertaste"><div class="meter"><div style="width: <?=$beerdoc->review_summary->aftertaste_avg/5*100?>%"></div></div></div>
	</div>

	<span class="label">Brewer's description:</span>
		<input id="edit_button" type="button" value="Edit This" />
	<div id="beer" class="triangle-border top">
		
		<input type="hidden" id="beer_id" value="<?=$beerdoc->id?>" />
		<input type="hidden" id="beer_srm" value="<?=$beerdoc->srm?>" />
		<input type="hidden" id="beer_availability" value="<?=$beerdoc->availability?>" />
		<input type="hidden" id="beer_name" value="<?=$beerdoc->name?>" />
		<input type="hidden" id="beer_style" value="<?=$beerdoc->styles[0]?>" />
		<div id="beer_description" class="editable_textarea"><?=$beerdoc->description?></div>
		<div class="cf"><div class="label">Style: </div><div id="beer_stylename"><?=$styles_lookup[$beerdoc->styles[0]]->name?></div></div>
		
		<div class="cf"><div class="label">Color: </div><div id="beer_srm_name"><div <?php if (!is_null($color)):?>style="background-color:<?=sprintf("#%02x%02x%02x",$color->rgb[0],$color->rgb[1],$color->rgb[2])?>"<?php endif;?>></div><?=$color->name?>&nbsp;</div></div>
		
		<div class="cf"><div class="label">Alcohol (abv): </div><div id="beer_abv"><?=$beerdoc->abv?>&#37;</div></div>
		<div class="cf"><div class="label">Bitterness (IBUs): </div><div id="beer_ibu"><?=$beerdoc->ibu?></div></div>
		<div class="cf"><div class="label">Original Gravity: </div><div id="beer_og"><?=$beerdoc->og?></div></div>
		<div class="cf"><div class="label">Final Gravity: </div><div id="beer_fg"><?=$beerdoc->fg?></div></div>
		<div class="cf"><div class="label">Hops: </div><div id="beer_hops"><?=$beerdoc->hops?></div></div>
		<div class="cf"><div class="label">Grains: </div><div id="beer_grains"><?=$beerdoc->grains?></div></div>
		<div class="cf"><div class="label">Yeast: </div><div id="beer_yeast"><?=$beerdoc->yeast?></div></div>
		
		
		<div class="cf"><div class="label">Availability: </div><div id="beer_availability_name"><?=$availability_titles[$beerdoc->availability]?></div></div>
		
	</div>

	<div id="beer_edit" class="triangle-border top hidden">
		
		<textarea id="beer_description_edit" rows="6" cols="55"></textarea>
		<div class="cf"><div class="label">Style: </div><input type="text" id="beer_stylename_edit" /><input type="hidden" id="beer_style_edit" value="<?=$beerdoc->styles[0]?>" /></div>
		
		<div class="cf"><div class="label">Color: </div><select id="beer_srm_edit" class="swatchselect2" size="1">
			<option value="0" style="background-color: rgb(255,255,255)"></option>
			<option value="2" style="background-color: rgb(255,249,180)">Pale Straw</option>
			<option value="3" style="background-color: rgb(255,216,120)">Straw</option>
			<option value="4" style="background-color: rgb(255,191,66)">Pale Gold</option>
			<option value="6" style="background-color: rgb(248,166,0)">Deep Gold</option>
			<option value="9" style="background-color: rgb(229,133,0)">Pale Amber</option>
			<option value="12" style="background-color: rgb(207,105,0)">Medium Amber</option>
			<option value="15" style="background-color: rgb(187,81,0)">Deep Amber</option>
			<option value="18" style="background-color: rgb(166,62,10)">Amber Brown</option>
			<option value="21" style="background-color: rgb(100,52,10)">Brown</option>
			<option value="24" style="background-color: rgb(78,11,10)">Ruby Brown</option>
			<option value="30" style="background-color: rgb(54,8,10)">Deep Brown</option>
			<option value="40" style="background-color: rgb(0,0,0)">Black</option>
		</select></div>
		
		<div class="cf"><div class="label">Alcohol (abv): </div><input type="text" id="beer_abv_edit" /></div>
		<div class="cf"><div class="label">Bitterness (IBUs): </div><input type="text" id="beer_ibu_edit" /></div>
		<div class="cf"><div class="label">Original Gravity: </div><input type="text" id="beer_og_edit" /></div>
		<div class="cf"><div class="label">Final Gravity: </div><input type="text" id="beer_fg_edit" /></div>
		<div class="cf"><div class="label">Name: </div><input type="text" id="beer_name_edit" /></div>
		<div class="cf"><div class="label">Hops: </div><input type="text" id="beer_hops_edit" /></div>
		<div class="cf"><div class="label">Grains: </div><input type="text" id="beer_grains_edit" /></div>
		<div class="cf"><div class="label">Yeast: </div><input type="text" id="beer_yeast_edit" /></div>

		<div class="cf"><div class="label">Availability: </div><select id="beer_availability_edit">
			<option value=""></option>
			<option value="year-round">Year-round</option>
			<option value="spring">Spring</option>
			<option value="summer">Summer</option>
			<option value="fall">Fall</option>
			<option value="winter">Winter</option>
			<option value="seasonal">Seasonal</option>
			<option value="limited">Limited</option>
		</select></div>
		<div id="editable_save_msg"></div>
		
	</div>


<h3 id="ratings"><?=count($reviews->reviews)?> Reviews</h3>

<?php foreach ($reviews->reviews as $review) :?>
<div class="areview">
	<img src="<?=empty($users[$review->user_id]->avatar)?"/img/default_avatar.gif":$users[$review->user_id]->avatar?>" style="width:30px"><span class="user"><a href="/user/<?=$review->user_id?>"><?=empty($users[$review->user_id]->name)?"Anonymous":$users[$review->user_id]->name?></a> posted <span class="datestring"><?=date('D, d M Y H:i:s O',$review->meta->timestamp)?></span></span>
	<div class="triangle-border top">
		<div class="star_rating" title="Rating: <?=$review->rating?> of 5"><div id="avgrating" style="width: <?=$review->rating/5*100?>%"></div></div>
		<div><?=$review->comments?></div>
		<div><?php
			$flavor_titles=array();
			if (isset($review->flavors))
			{
				foreach ($review->flavors as $flavor){$flavor_titles[]=$flavor_lookup[$flavor];}
			}
			print join(', ',$flavor_titles);
		?></div>
		<div id="ratings_section" class="cf">
			<div class="body" title="Body: <?=$review->body?> of 5"><div class="meter"><div style="width: <?=$review->body/5*100?>%"></div></div></div>
			<div class="balance" title="Balance: <?=$review->balance?> of 5"><div class="meter"><div style="width: <?=$review->balance/5*100?>%"></div></div></div>
			<div class="aftertaste" title="Aftertaste: <?=$review->aftertaste?> of 5"><div class="meter"><div style="width: <?=$review->aftertaste/5*100?>%"></div></div></div>
		</div>
		<div class="cf"><div class="label">Date Drank: </div><span class="datestring"><?=!empty($review->date_drank)?date('D, d M Y H:i:s O',strtotime($review->date_drank)):''?></span></div>
		<div class="cf"><div class="label">Price: </div>$<?=$review->purchase_price?> at <a href="/<?=str_replace(':','/',$review->purchase_place_id)?>"><?=$places[$review->purchase_place_id]->name?></a></div>
		<div class="cf"><div class="label">Poured: </div><?=$review->poured_from?></div>
	</div>
</div>
<?php endforeach; ?>
<!-- <div id="reviewdata"></div> -->

</div>

	<div id="mwr_right_300">
	<?php if (isset($recommends->similar)):?>
	<h2>Similar Beers to This Beer</h2>
	<ul class="otherlist">
		<?php foreach($recommends->similar as $recommend) :?>
			<li><?php if ($recommend->photos->total):?><img src="<?=$recommend->photos->thumbnail?>" /><?php endif?>
			<a href="/<?=BeerCrush::docid_to_docurl($recommend->brewery->id)?>" class="brewery"><?=$recommend->brewery->name?></a>
			<a href="/<?=BeerCrush::docid_to_docurl($recommend->id)?>"><?=$recommend->name?></a>
			<div class="star_rating" title="Rating: <?=$recommend->review_summary->avg?> of 5"><div id="avgrating" style="width: <?=$recommend->review_summary->avg/5*100?>%"></div></div></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>


	<?php if (isset($recommends->beer)):?>
	<h2>People Who Like This Beer, Also Like</h2>
	<ul class="otherlist">
		<?php foreach($recommends->beer as $recommend) :?>
			<li><?php if ($recommend->photos->total):?><img src="<?=$recommend->photos->thumbnail?>" /><?php endif?>
			<a href="/<?=BeerCrush::docid_to_docurl($recommend->brewery->id)?>" class="brewery"><?=$recommend->brewery->name?></a>
			<a href="/<?=BeerCrush::docid_to_docurl($recommend->id)?>"><?=$recommend->name?></a>
			<div class="star_rating" title="Rating: <?=$recommend->review_summary->avg?> of 5"><div id="avgrating" style="width: <?=$recommend->review_summary->avg/5*100?>%"></div></div></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>

	<?php if (count($beerdoc->styles)):?>
	<h2>Other <?foreach ($beerdoc->styles as $styleid):?><?=$styles_lookup[$styleid]->name?><?endforeach?>s</h2>
	<ul class="otherlist">
		<?php foreach ($other_by_style as $id):
			$b=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($id));
			$b->brewery=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($id)));
		?>
			<li><?php if ($b->photos->total):?><img src="<?=$b->photos->thumbnail?>" /><?php endif?><a href="/<?=BeerCrush::docid_to_docurl($id)?>"><?=$b->name?></a> by <a href="/<?=BeerCrush::docid_to_docurl($b->brewery->id)?>"><?=$b->brewery->name?></a> (<?=$b->review_summary->avg?>)</li>
		<?php endforeach;?>
	</ul>
	<?php endif;?>
	<?php 
	// Remove this beer from beerlist so we don't randomly pick it for the list of other beers
	for ($i=0,$j=count($beerlist->beers);$i<$j;++$i) {
		if ($beerlist->beers[$i]->beer_id==$beerdoc->id) {
			array_splice($beerlist->beers,$i,1);
			break;
		}
	}
	if (count($beerlist->beers)): 
	?>
	<h2>More from <?=$brewerydoc->name?></h2>	
	<ul class="otherlist">
		<?php
		for ($i=0,$j=4;$i<$j;++$i) :
			$n=rand(0,count($beerlist->beers)-1);
			$u=BeerCrush::docid_to_docurl($beerlist->beers[$n]->beer_id);
			$morebeer=BeerCrush::api_doc($oak,$u);
			// Remove it so it isn't selected again
			array_splice($beerlist->beers,$n,1);
		?>
			<li><?php if ($morebeer->photos->total):?><img src="<?=$morebeer->photos->thumbnail?>" /><?php endif?><a href="/<?=$u?>"><?=$morebeer->name?></a> (<?=$morebeer->review_summary->avg?>)</li>
		<?php endfor; ?>
	</ul>
	<?php endif;?>
	</div>
</div>
<div id="mwr_left_250">
	<?php if ($beerdoc->photos->total==0):?>
		<img src="/img/beer.png" />
	<?php else: ?>
	<?php foreach ($photoset->photos as $photo) :?>
	<div class="photo">
		<img src="<?=$photo->url?>?size=small" /><p class="caption">by <a href="/user/<?=$photo->user_id?>"><img src="<?=empty($users[$photo->user_id]->avatar)?"/img/default_avatar.gif":$users[$photo->user_id]->avatar?>" /><?=$users[$photo->user_id]->name?></a> <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$photo->timestamp)?></span></p>
	</div>
	<?php endforeach; ?>
	<?php endif;?>

<div id="new_photos"></div>

<input id="photo_upload" name="photo" type="file" />

<p></p>
<ul class="command">
	<li style="background-image: url('/img/wishlist.png')"><a id="add_to_wishlist_link" href="#">Add to My Wishlist</a></li>
	<li style="background-image: url('/img/ratebeer.png')"><a href="" id="ratebeer">Rate this Beer</a></li>
	<li style="background-image: url('/img/nearby.png')"><a id="find_this_nearby_link" href="#">Find this Nearby</a><br />
	Zip <input id="find_this_nearby_zip" size="10"> <button id="find_this_nearby_button">Find</button></li>
</ul>
<div id="nearby_results"></div>
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
</div>

<div id="beer_review_form" class="hidden"></div>

<script type="text/javascript">

<?php
// Make javascript variable colors_strings from $colors so they can be used in javascript
print "var colors_strings=\n".json_encode($colors->colors).";\n";

$kv=array();
foreach ($availability_titles as $id=>$name) {
	$kv[]="\"$id\":\"$name\"";
}
print "var availability_strings={\n".join(",\n",$kv)."\n};\n";


?>

function undo_photo(uuid,url) {
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
				diffs[p]['new']=b[p];
			}
			else if (typeof(b[p])=='undefined') {
				diffs[p].old=a[p];
				diffs[p]['new']=null;
			}
			else {
				diffs[p].old=a[p];
				diffs[p]['new']=b[p];
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
				diffs[p]['new']=b[p];
			}
		}
	}

	return diffs;
}

var exclude_props=[ '_rev', 'meta' ];

function display_diffs(vindex,diffs) {
	for (var prop in diffs) {
		if (jQuery.inArray(prop,exclude_props)==-1) {
			if (typeof(diffs[prop].old) == 'undefined' && typeof(diffs[prop]['new']) == 'undefined') {
				display_diffs(vindex,diffs[prop]);
			}
			else {
				var s;
				if (diffs[prop].old!=null && diffs[prop].old.length) {
					s='Changed '+prop+' from <span class="version_change_from">'+diffs[prop].old+'</span> to <span class="version_change_to">'+diffs[prop]['new']+'</span>';
				}
				else {
					s='Added '+prop+': <span class="version_change_new">'+diffs[prop]['new']+'</span>';
				}
				s='<div>'+s+'</div>';
				$('#version_'+vindex).append(s);
			}
		}
	}
	
}

function show_diff(a,b) {
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

function find_beer_nearby(lat,lon) {
	$('#nearby_results').empty(); // Clear it first
	$.get('/api/nearby_beer.fcgi',
		{
			"beer_id": $('#beer_id').val(),
			"within": 20,
			"lat": lat,
			"lon": lon
		},
		function(data) {
			for (i=0,j=data.places.length;i<j;++i) {
				$('#nearby_results').append('<div><a href="/'+data.places[i].place_id.replace(/:/g,'/')+'">'+data.places[i].name+'</a></div>');
			}
		}
	)
	
}


function pageMain()
{
	$('#post_review_button').click(function(){
		// $('#reviewdata').text($('#review_form').serialize());
		$('#review_result_msg').text(); // Clear last message, if any
		$(this).ajaxError(function(evt,xhr,options,err) {
			if (options.url=='/api/beer/review') {
				doc=$.parseJSON(xhr.responseText);
				$('#review_result_msg').removeClass('hidden feedback_error');
				$('#review_result_msg').addClass('feedback_success');
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
		$('#review_result_msg').removeClass('hidden feedback_error');
		$('#review_result_msg').addClass('feedback_success');
		$('#review_result_msg').html('Your rating was posted and will appear here in a few hours.');
	});
	
	$('#add_to_wishlist_link').click(function(){
		$.post('/api/wishlist',
			{'add_item':$('#beer_id').val()},
			function(data){
			}
		);
		return false;
	});

	$('#find_this_nearby_link').click(function(){
		if(navigator.geolocation) {
		    browserSupportFlag = true;
		    navigator.geolocation.getCurrentPosition(function(position) {
				find_beer_nearby(position.coords.latitude,position.coords.longitude);
		    }, function() {
		    });
		}
	});
	
	$('#find_this_nearby_button').click(function(){

		$('#find_this_nearby_zip').val($.trim($('#find_this_nearby_zip').val()));
		if ($('#find_this_nearby_zip').val().length) {
			var geocoder = new google.maps.Geocoder();
			geocoder.geocode(
				{address: $('#find_this_nearby_zip').val()},
				function(results,status){
					if (status == google.maps.GeocoderStatus.OK) {
						find_beer_nearby(results[0].geometry.location.lat(),results[0].geometry.location.lng())
					}
				}
			);
		}
	});

	// $.getScript('/js/beerstyles.js',function(data,textStatus){
	// 	$("#beer_stylename_edit").autocomplete(beerstyles,{
	// 		"mustMatch": true,
	// 		"matchContains": true,
	// 		"formatItem": function(item) {
	// 			return item.name;
	// 		}
	// 	}).result(function(evt,item) {
	// 		$('#beer_style_edit').val(item.id);
	// 		$('#beer_stylename').val(item.name);
	// 	});
	// });
	

	$('#beer').editabledoc('/api/beer/edit',{
		args: {
			beer_id: $('#beer_id').val()
		},
		stripprefix: 'beer_',
		fields: {
			'beer_name': {
				postSuccess: function(name,value) {
					$('#main h1').html(value); // Change the H1 tag on the page (the beer name)
				}
			},
			'beer_style': {
				postName: 'styles',
				displayValueToEditValue: function() {return $('#beer_style').val();},
				saveEditValue: function(value) { $('#beer_style').val(value); },
				postSuccess: function(name,value) {
					$('#'+name).val(value[0]);
					// Find the beer style id
					$.each(beerstyles,function(idx,elem){
						if (elem.id==value[0]) {
							$('#beer_stylename').text(elem.name);
							return false; // Break out of each() loop
						}
					})
					
				}
			},
			'beer_srm': {
				displayValueToEditValue: function() {return $('#beer_srm').val();},
				saveEditValue: function(value) { 
					$('#beer_srm').val(value); 
					
					var c=jQuery.grep(colors_strings,function(item,idx){ return item.srm==value; });
					$('#beer_srm_name').html('<div style="background-color:rgb('+c[0].rgb[0]+','+c[0].rgb[1]+','+c[0].rgb[2]+')"></div>'+c[0].name+'&nbsp;');
				},
				postSuccess: function(name,value) {
					var c=jQuery.grep(colors_strings,function(item,idx){ return item.srm==value; });
					$('#beer_srm').val(value);
					$('#beer_srm_name').html('<div style="background-color:rgb('+c[0].rgb[0]+','+c[0].rgb[1]+','+c[0].rgb[2]+')"></div>'+c[0].name+'&nbsp;');
					
				}
			},
			'beer_availability': {
				displayValueToEditValue: function() {return $('#beer_availability').val();},
				saveEditValue: function(value) { $('#beer_availability').val(value); },
				postSuccess: function(name,value) {
					$('#beer_availability').val(value);
					$('#beer_availability_name').html(availability_strings[value]);
				}
			},
			'beer_abv': {
				postSuccess: function(name,value) { $('#'+name).html(value+'&#37;'); /* Add % sign */ }
			}
		}
	});
	
	$('#photo_upload').uploadify({
		'uploader'  : '/flash/uploadify.swf',
		'script'    : '/api/beer/photo',
		'cancelImg' : '/img/uploadify/cancel.png',
		'auto'      : true,
		'multi'     : true,
		'fileDataName': 'photo',
		'fileDesc'	: 'Upload a photo',
		'fileExt'	: '*.jpg;*.jpeg;*.png',
		'buttonText': "POST A PHOTO", 
		'sizeLimit' : 20000000,
		'width' : 230,  
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
	
	// show_history();
	
	if (get_user_id()!=null) {
		$.getJSON('/api/'+$('#beer_id').val().replace(/:/g,'/')+'/personalization',null,function(data){
			$('#predrating').parent('div').attr('title','Predicted rating for you: '+data.predictedrating+' out of 5');
			$('#predrating').css('width',data.predictedrating/5*100+'%');
		});
	}

	$('#ratebeer').click(function(evt){

		if (get_user_id()==null) { // Not logged in
			show_login_dialog(function(){ // Successful login
			       $.getScript("/js/beerreview.js");
			});
		}
		else {
			$.getScript("/js/beerreview.js");
		}
		return false;
	});
	
}

</script>
<?php include("../footer.php"); ?>
