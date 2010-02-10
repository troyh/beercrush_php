<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
	
$beerdoc=json_decode(file_get_contents($oak->get_config_info()->api->base_uri."/beer/".str_replace(':','/',$_GET['beer_id'])));
$brewery_id=preg_replace('/:[^:]*$/','',$_GET['beer_id']);
$brewerydoc=json_decode(file_get_contents($oak->get_config_info()->api->base_uri."/brewery/".$brewery_id));
$reviews=json_decode(file_get_contents($oak->get_config_info()->api->base_uri."/review/beer/".str_replace(':','/',$_GET['beer_id']).'/0'));
$flavors=json_decode(file_get_contents($oak->get_config_info()->api->base_uri."/flavors"));

// Build flavor id lookup table
$flavor_lookup=array();
build_flavor_lookup_table($flavors->flavors);

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
			print '<div>'.$flavor->title.':';
			output_flavors($flavor->flavors);
			print '</div>';
		}
		else
		{
			print '<input type="checkbox" name="flavors[]" value="'.$flavor->id.'" />'.$flavor->title;
		}
	}
}
include("header.php");
?>

<a id="brewery_link" href="/brewery/<?=preg_replace('/^.*:/','',$brewerydoc->{"@attributes"}->id)?>"><?=$brewerydoc->name?></a>
<h2 id="beer_title"><?=$beerdoc->name?></h2>

<div id="description"><?=$beerdoc->description?></div>

<div>OG: <?=$beerdoc->og?></div>
<div>FG: <?=$beerdoc->fg?></div>
<div>ABV: <?=$beerdoc->abv?>%</div>
<div>IBU: <?=$beerdoc->ibu?></div>
<div>Grains:</div>
<div>Yeast:</div>
<div>Beer last modified: <span class="datestring"><?=date('D, d M Y H:i:s O',$beerdoc->meta->mtime)?></span></div>
<h3>Photos</h3>
<h3><?=count($reviews->reviews)?> Reviews</h3>

<?php
$places=array();
$users=array();
foreach ($reviews->reviews as $review)
{
	if (!isset($places[$review->purchase_place_id]))
	{
		$places[$review->purchase_place_id]=json_decode(@file_get_contents($oak->get_config_info()->api->base_uri.'/'.str_replace(':','/',$review->purchase_place_id)));
	}
	if (!isset($users[$review->user_id]))
	{
		$users[$review->user_id]=json_decode(@file_get_contents($oak->get_config_info()->api->base_uri.'/user/'.$review->user_id));
	}
?>
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
<?php
}
?>

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
		Price: <input name="price" type="text" size="10" /> at <input type="place_id" size="40" />
	</div>
	<div>
		Flavors: <?=output_flavors($flavors->flavors)?>
	</div>
	<div>
		Comments:
		<textarea name="comments" rows="5" cols="80"></textarea>
	</div>
	
	<input id="post_review_button" type="button" value="Post my review" />
</form>

<div id="reviewdata"></div>

<script type="text/javascript">

function pageMain()
{
	$('#post_review_button').click(function(){
		$('#reviewdata').text($('#review_form').serialize());
		$.post('/api/beer/review',$('#review_form').serialize(),function(data){
			$('#reviewdata').text(data)
		});
	});
}

</script>

<?php include("footer.php"); ?>
