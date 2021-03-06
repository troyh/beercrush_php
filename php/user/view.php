<?php
require_once('beercrush/beercrush.php');

$userdoc=BeerCrush::api_doc($BC->oak,'user/'.$_GET['user_id']);
$reviews=BeerCrush::api_doc($BC->oak,'user/'.$_GET['user_id'].'/reviews');
$flavors=BeerCrush::api_doc($BC->oak,'flavors');

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

$flavor_lookup=array();
build_flavor_lookup_table($flavors->flavors);

include("../header.php");
?>

<div id="mwr">

<div id="main">

<div id="user">
	<h1 id="user_name"><?=empty($userdoc->name)?"Anonymous":$userdoc->name?></h1>
	<div>Joined <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$userdoc->meta->timestamp)?></span></div>
	<input type="hidden" id="user_id" value="<?=$userdoc->id?>" />
	<h2>About Me</h2>
	<div id="user_aboutme"><?=$userdoc->aboutme?></div>
</div>

<div id="user_edit" class="hidden">
	<input type="text" id="user_name_edit" value="<?=empty($userdoc->name)?"Anonymous":$userdoc->name?>" />
	<h2>About Me</h2>
	<textarea id="user_aboutme_edit" rows="5" cols="40"><?=$userdoc->aboutme?></textarea>
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
		<div class="star_rating"><div id="avgrating" style="width: <?=$review->rating/5*100?>%"></div></div>
		<?if (!empty($review->comments)):?><div class="comments"><?=$review->comments?></div><?endif?>
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

<div id="mwr_right_300">
<div id="for_your_eyes_only">
<h2>My Wishlist</h2>
	<ul id="wishlist">
	</ul>

<h2>My Beer Buddies</h2>
	<ul>
		<li>Person</li>
		<li>Person</li>
		<li>Person</li>
		<li>Person</li>
	</ul>

<h2>My Bookmarks</h2>
	<ul id="bookmarks">
	</ul>

</div>
</div>
</div>
<div id="mwr_left_250">

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
			if (data.gravatar_url)
				$('#avatar').append('I have a <a href="http://en.gravatar.com/">Gravatar</a>. <input type="button" value="Use my Gravatar" onclick="useGravatar();" />');

			// Put edit button
			$('#user').before('<input id="edit_button" type="button" value="Edit This" />');
			$('#user').editabledoc('/api/user/edit',{
				args: {
					user_id: $('#user_id').val()
				},
				stripprefix: 'user_',
				fields: {
					'user_name': {
						postSuccess: function(name,value) {
							$('#user_name').html(value); // Change the H1 tag on the page (the beer name)
						}
					}
				}
			});				

			// $('#editthis_button').click(function(){
			// });
		});

		$.getJSON('/api/wishlist/'+$.cookie('userid'),function(data,status){
			$.each(data.items,function(idx,item){
				$('#wishlist').append('<li><a href="/'+item.beer_id.replace(/:/g,'/')+'">'+item.name+'</a></li>');
			});
		})

		$.getJSON('/api/bookmarks/'+$.cookie('userid'),function(data,status){
			$.each(data.items,function(idx,item){
				$('#bookmarks').append('<li><a href="/'+idx.replace(/:/g,'/')+'">'+item.name+'</a></li>');
			});
		})
		
	}
}

</script>
<?php

include("../footer.php");

?>