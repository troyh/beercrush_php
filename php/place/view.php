<?php
require_once('beercrush/beercrush.php');

$oak=new OAK(BeerCrush::CONF_FILE);
$place=BeerCrush::api_doc($oak,'place/'.str_replace(':','/',$_GET['place_id']));
$beerlist=BeerCrush::api_doc($oak,'place/'.str_replace(':','/',$_GET['place_id']).'/menu');
$reviews=BeerCrush::api_doc($oak,'review/place/'.str_replace(':','/',$_GET['place_id']).'/0');
// var_dump($beerlist);exit;
// var_dump($place);exit;
// var_dump($reviews);exit;

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

<h2>Beers Available</h2>
<div id="beerlist">
	<table>
		<tr>
			<th>Brewery</th>
			<th>Beer</th>
			<th>Price</th>
			<th>Tap</th>
			<th>Cask</th>
			<th>Bottle (12 fl. oz.)</th>
			<th>Bottle (22 fl. oz.)</th>
			<th>Can</th>
		</tr>
	<?foreach ($beerlist->items as $item) :?>
	<tr>
		<td><a href="/<?=str_replace(':','/',$item->brewery->id)?>"><?=$item->brewery->name?></a></td>
		<td><a href="/<?=str_replace(':','/',$item->id)?>"><?=$item->name?></a></td>
		<td>$<?=number_format($item->price,2)?></td>

		<td><input <?=$item->ontap     ?'checked="checked"':''?> type="checkbox" value="tap"      name="serving_<?=str_replace(':','_',$item->id)?>" /></td>
		<td><input <?=$item->oncask    ?'checked="checked"':''?> type="checkbox" value="cask"     name="serving_<?=str_replace(':','_',$item->id)?>" /></td>
		<td><input <?=$item->inbottle  ?'checked="checked"':''?> type="checkbox" value="bottle"   name="serving_<?=str_replace(':','_',$item->id)?>" /></td>
		<td><input <?=$item->inbottle22?'checked="checked"':''?> type="checkbox" value="bottle22" name="serving_<?=str_replace(':','_',$item->id)?>" /></td>
		<td><input <?=$item->incan     ?'checked="checked"':''?> type="checkbox" value="can"      name="serving_<?=str_replace(':','_',$item->id)?>" /></td>

	</tr>
	<?endforeach;?>
	</table>
</div>

<h2>Reviews</h2>
<div id="reviewlist">
</div>

<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function pageMain()
{
	makeDocEditable('#place','place_id','/api/place/edit');
	
	$('#beerlist input[type=checkbox]').change(function(evt){
		var serving_types=[];
		var n=$(evt.target).attr('name').replace(/^serving_/,'').replace(/_/g,':');
		$('#beerlist input[name='+$(evt.target).attr('name')+']').each(function(){if ($(this).attr('checked')) serving_types[serving_types.length]=$(this).val();});

		$.post('/api/menu/edit', {
			"place_id": $('#place_id').val(),
			"add_item": n+';'+serving_types.join(',')
		});
	});
}

</script>
<?
include("../footer.php");
?>