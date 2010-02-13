<?php
require_once('beercrush/oak.class.php');
$oak=new OAK;

$doc=@file_get_contents($oak->get_config_info()->api->base_uri."/brewery/".$_GET['brewery_id']);
$brewerydoc=json_decode($doc);

$doc=@file_get_contents($oak->get_config_info()->api->base_uri."/brewery/".$_GET['brewery_id']."/beerlist");
$beerlistdoc=json_decode($doc);
if ($beerlistdoc==null)
{
	$beerlistdoc->beers=array();
}

include("../header.php");
?>

<div id="brewery">
	<input type="hidden" id="brewery_id" value="<?=$brewerydoc->id?>">
	<h1 id="brewery_name"><?=$brewerydoc->name?></h1>

	<div id="address">
		<div id="brewery_address:street"><?=$brewerydoc->address->street?></div>
		<span id="brewery_address:city"><?=$brewerydoc->address->city?></span>, 
		<span id="brewery_address:state"><?=$brewerydoc->address->state?></span> 
		<span id="brewery_address:country"><?=$brewerydoc->address->country?></span>
	</div>

	<div id="brewery_phone"><?=$brewerydoc->phone?></div>
	<div><span id="brewery_uri"><?=$brewerydoc->uri?></span> <span><a href="<?=$brewerydoc->uri?>">Visit web site</a></span></div>

	<div id="editable_save_msg"></div>
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />

</div>

<h3>Beers</h3>
<div id="beerlist">
<?php foreach ($beerlistdoc->beers as $beer){ ?>
	<div><a href="/<?=str_replace(':','/',$beer->beer_id)?>"><?=$beer->name?></a></div>
<?php } ?>
</div>

<h3>Add a beer</h3>
<p>
	Give the new beer a name and press the Add button. The name of the beer should just be the name without the 
	brewery&apos;s name. For example, "Pale Ale" rather than "Sierra Nevada Pale Ale".
</p>

<p>
	Once it's added, you'll be able to give it a description and specify the details (style, IBUs, ABV, etc.) if 
	you know them. If you don't know them, that's okay, someone else does and will eventually provide them.
</p>

<form id="new_beer_form" method="post" action="/api/beer/edit">
	<input type="hidden" name="brewery_id" value="<?=$brewerydoc->id?>" />
	<input type="text" size="30" name="name" value="" />
	<input type="submit" value="Add" />
	<div id="new_beer_msg"></div>
</form>
	
<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function pageMain()
{
	makeDocEditable('#brewery','brewery_id','/api/brewery/edit');
	
	$('#new_beer_form').submit(function() {
		$('#new_beer_msg').text('Adding...');
		$('#new_beer_form').ajaxError(function(e,xhr,options,exception) {
			if (options.url=='/api/beer/edit') {
				if (xhr.status==409) { // Duplicate beer
					$('#new_beer_msg').html("There's already a beer with that name.");
				}
			}
		});
		
		$.post(
			$(this).attr('action'),
			$('#new_beer_form').serialize(),
			function(data,status,xhr){
				$('#new_beer_msg').html(data.name+' added! <a href="/'+data.id.replace(/:/g,'/')+'">Edit it</a>');
				
				$('#beerlist').append('<div><a href="/'+data.id.replace(/:/g,'/')+'">'+data.name+'</a></div>');
			},
			'json'
		);
		return false;
	});
}

</script>


<?php
	include("../footer.php");
?>