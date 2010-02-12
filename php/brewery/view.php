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
	
<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function pageMain()
{
	makeDocEditable('#brewery','brewery_id','/api/brewery/edit');
}

</script>


<?php
	include("../footer.php");
?>