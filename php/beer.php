<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
	
$doc=file_get_contents($oak->get_config_info()->api->base_uri."/beer/".str_replace(':','/',$_GET['beer_id']));
$beerdoc=json_decode($doc);
// print_r($beerdoc);exit;
$brewery_id=preg_replace('/:[^:]*$/','',$_GET['beer_id']);
$doc=file_get_contents($oak->get_config_info()->api->base_uri."/brewery/".$brewery_id);
$brewerydoc=json_decode($doc);
// print_r($brewerydoc);exit;

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
<div>Photos</div>
<div>Reviews</div>

<div>Beer last modified: <?=date('F j, Y g:i:sa',$beerdoc->meta->mtime)?></div>

<script type="text/javascript">

function BeerCrushMain()
{
	// $.getJSON("/json/beer/"+getUrlVars()["id"],null,function(data,textStatus) {
	// 	$('#beer_title').text(data.name);
	// 	$('#description').text(data.description);
	// });
	// var brewery_id=getUrlVars()['id'].replace(/\/.*$/,'');
	// $.getJSON("/json/brewery/"+brewery_id,null,function(data,textStatus) {
	// 	$('#brewery_link').attr('href','/brewery/brewery.html?id='+brewery_id);
	// 	$('#brewery_link').text(data.name);
	// })
}

</script>

<?php include("footer.php"); ?>
