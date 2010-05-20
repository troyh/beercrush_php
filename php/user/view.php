<?php
require_once('beercrush/beercrush.php');

$userdoc=BeerCrush::api_doc($BC->oak,'user/'.$_GET['user_id']);
$reviews=BeerCrush::api_doc($BC->oak,'user/'.$_GET['user_id'].'/reviews');

include("../header.php");
?>

<div id="main">

<div id="mainwithright">

<div id="user">
	<h1 id="user_name"><?=empty($userdoc->name)?"Anonymous":$userdoc->name?></h1>

	<div>Joined <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$userdoc->meta->timestamp)?></span></div>
	

	<input type="hidden" id="user_id" value="<?=$userdoc->id?>" />

	<h2>About Me</h2>
	<div id="user_aboutme"><?=$userdoc->aboutme?></div>

	<div id="editable_save_msg"></div>
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />

</div>


<h2>My <?=count($reviews->reviews)?> Reviews</h2>
<? 
foreach ($reviews->reviews as $review) {	
	$review_type=BeerCrush::get_review_type($review->id); 
?>
<div class="areview">
	<div class="type">
		<div class="<?=$review_type?>"></div>
	<?if ($review_type=='beer') {?>
		<a href="/<?=BeerCrush::docid_to_docurl($BC->docobj(BeerCrush::beer_id_to_brewery_id($review->beer_id))->id)?>" class="brewery"><?=$BC->docobj(BeerCrush::beer_id_to_brewery_id($review->beer_id))->name?></a> <a href="/<?=BeerCrush::docid_to_docurl($review->beer_id)?>"><?=$BC->docobj($review->beer_id)->name?></a>
	<?} else if ($review_type=='place'){?>
		<a href="/<?=BeerCrush::docid_to_docurl($review->place_id)?>"><?=$BC->docobj($review->place_id)->name?></a>
	<?}?>
	</div>
	<span class="user"><?=empty($userdoc->name)?"Anonymous":$userdoc->name?> posted <span class="datestring"><?=date('D, d M Y H:i:s O',$review->meta->timestamp)?></span></span>
	<div class="triangle-border top">
		<div class="star_rating"><div id="avgrating" style="width: <?=$review->rating?>0%"></div></div>
		<?if (!empty($review->comments)):?><div><?=$review->comments?></div><?endif?>
		<?if ($review_type=='beer') {?>
			<div><?php
				$flavor_titles=array();
				if (isset($review->flavors))
				{
					foreach ($review->flavors as $flavor){$flavor_titles[]=$flavor_lookup[$flavor];}
				}
				print join(', ',$flavor_titles);
			?></div>
			<div class="cf"><div class="label">Body: </div><?=$review->body?></div>
			<div class="cf"><div class="label">Balance: </div><?=$review->balance?></div>
			<div class="cf"><div class="label">Aftertaste: </div><?=$review->aftertaste?></div>
			<div class="cf"><div class="label">Date Drank: </div><span class="datestring"><?=!empty($review->date_drank)?date('D, d M Y H:i:s O',strtotime($review->date_drank)):''?></span></div>
			<div class="cf"><div class="label">Price: </div>$<?=$review->purchase_price?> at <a href="/<?=str_replace(':','/',$review->purchase_place_id)?>"><?=$places[$review->purchase_place_id]->name?></a></div>
			<div class="cf"><div class="label">Poured: </div><?=$review->poured_from?></div>
		<?} else if ($review_type=='place'){?>
			<div class="cf"><div class="label">Service: </div><!--TROY TODO--></div>
			<div class="cf"><div class="label">Atmosphere: </div><!--TROY TODO--></div>
			<div class="cf"><div class="label">Food: </div><!--TROY TODO--></div>
		<?}?>
	</div>
</div>
<?
}
?>

</div>

<div id="rightcol">
<h2>My Wishlist</h2>
	<ul>
		<li>Beer</li>
		<li>Beer</li>
		<li>Beer</li>
		<li>Beer</li>
	</ul>

<h2>My Beer Buddies</h2>
	<ul>
		<li>Person</li>
		<li>Person</li>
		<li>Person</li>
		<li>Person</li>
	</ul>

</div>
</div>
<div id="leftcol">

	<div id="avatar">
	<img src="<?=empty($userdoc->avatar)?"/img/default_avatar.gif":$userdoc->avatar?>" />
	</div>
	<ul class="command">
		<li style="background-image: url('/img/wishlist.png')"><a href="">Add <strong><?=empty($userdoc->name)?"Anonymous":$userdoc->name?></strong> to My Beer Buddies</a></li>
	</ul>
	
</div>

<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function useGravatar()
{
	if (userinfo) {
		// See if their Gravatar really exists
		$.get(userinfo.gravatar_url,
			null,
			function(data,status,xhr){
				// The Gravatar does exist, use it
				$.post('/api/user/edit',
					{"user_id": userinfo.userid, "avatar": userinfo.gravatar_url},
					function(data,status,xhr) {
						$('#avatar img').attr('src',userinfo.gravatar_url);
					},
					'json'
				);
			}
		);
	}
}

var userinfo=null;

function pageMain()
{
	// If I'm logged in, see if this page is mine
	if ('user:'+$.cookie('userid')==$('#user_id').val()) {
		// It's me (I think), let's request my userdoc and see if the server gives it to me.
		$.getJSON('/api/user/fullinfo',function(data,status) {
			// This is my page
			userinfo=data;
			makeDocEditable('#user','user_id','/api/user/edit');
			if (userinfo.gravatar_url)
				$('#avatar').append('I have a <a href="http://en.gravatar.com/">Gravatar</a>. <input type="button" value="Use my Gravatar" onclick="useGravatar();" />');
		});
	}

}

</script>
<?php

include("../footer.php");

?>