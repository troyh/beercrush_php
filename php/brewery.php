<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;

$doc=file_get_contents($oak->get_config_info()->api->base_uri."/brewery/".$_GET['id']);
$brewerydoc=json_decode($doc);

$doc=@file_get_contents($oak->get_config_info()->api->base_uri."/brewery/".$_GET['id']."/beerlist");
$beerlistdoc=json_decode($doc);
if ($beerlistdoc==null)
{
	$beerlistdoc->beers=array();
}

$attributes='@attributes';
// var_dump($beerlistdoc);exit;

include("header.php");
?>

<h1 id="brewery_title"><?=$brewerydoc->name?></h1>

<div id="address">
	<div id="street"><?=$brewerydoc->address->street?></div>
	<span id="city"><?=$brewerydoc->address->city?></span>, <span id="state"><?=$brewerydoc->address->state?></span> <span id="country"><?=$brewerydoc->address->country?></span>
</div>

<div id="phone"><?=$brewerydoc->phone?></div>
<a id="url" href="<?=$brewerydoc->uri?>"><?=$brewerydoc->uri?></a>

<h3>Beers</h3>
<div id="beerlist">
<?php foreach ($beerlistdoc->beers as $beer){ ?>
	<div><a href="/<?=str_replace(':','/',$beer->id)?>"><?=$beer->name?></a></div>
<?php } ?>
</div>
	
<script type="text/javascript">

function BeerCrushMain()
{
	// $.getJSON("/json/brewery/"+getUrlVars()["id"],null,function(data,textStatus) {
	// 	$("#brewery_title").text(data.name);
	// 	$("#address #street").text(data.address.street);
	// 	$("#address #city").text(data.address.city);
	// 	$("#address #state").text(data.address.state);
	// 	$("#address #country").text(data.address.country);
	// 	$("#phone").text(data.phone);
	// 	$("#url").text(data.uri);
	// 	$("#url").attr('href',data.uri);
	// });
	// $.getJSON("/json/brewery/"+getUrlVars()["id"]+"/beerlist",null,function(data,textStatus) {
	// 	jQuery.each(data.beers,function(k,v) {
	// 		$("#beerlist").append("<div><a href=\"/beer/beer.html?id="+v['@attributes'].id.replace(/^beer:/,'').replace(/:/g,'/')+"\">"+v.name+"</a></div>");
	// 	})
	// });
}

</script>


<?php
	include("footer.php");
?>