<?php
require_once('OAK/oak.class.php');

$oak=new OAK;
$place=json_decode(file_get_contents($oak->get_config_info()->api->base_uri.'/place/'.str_replace(':','/',$_GET['place_id'])));
// var_dump($place);exit;

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
		<div id="place_address:street"><?=$place->address->street?></div>
		<span id="place_address:city"><?=$place->address->city?></span>, <span id="place_address:state"><?=$place->address->state?></span> <span id="place_address:country"><?=$place->address->country?></span>
	</div>

	<div id="place_phone"><?=$place->phone?></div>
	<div>
		<span id="place_uri" href="<?=$place->uri?>"><?=$place->uri?></span>
		<a href="<?=$place->uri?>">Visit web site</a>
	</div>

	<div id="place_description"><?=$place->description?></div>

	<h2>Details</h2>
	<div>Kid-Friendly: <?php echo isset($place->kid_friendly)?($place->kid_friendly?'Yes':'No'):'Unknown'; ?></div>
	<div>Outdoor seating: <?php echo isset($place->restaurant->outdoor_seating)?($place->restaurant->outdoor_seating?'Yes':'No'):'Unknown'; ?></div>
	<div>Wi-Fi: <?php echo isset($place->wifi)?($place->wifi?'Yes':'No'):'Unknown'; ?></div>

	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />
	
</div>

<h2>Beers</h2>
<div id="beerlist">
</div>
<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function pageMain()
{
	makeDocEditable('#place','place_id','/api/place/edit');
}

</script>
<?
include("../footer.php");
?>