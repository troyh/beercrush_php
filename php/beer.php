<?php
	header('Content-type: text/html; charset=utf-8');
	
	$doc=file_get_contents("http://localhost/api/beer/".str_replace(':','/',$_GET['id']));
	$beerdoc=json_decode($doc);

	$brewery_id=preg_replace('/:[^:]*$/','',$_GET['id']);
	$doc=file_get_contents("http://localhost/api/brewery/".$brewery_id);
	$brewerydoc=json_decode($doc);
	// print_r($brewerydoc);exit;
	print file_get_contents("../html/header.html");
?>

<a id="brewery_link" href="/brewery/<?=preg_replace('/^.*:/','',$brewerydoc->{'@attributes'}->id)?>"><?=$brewerydoc->name?></a>
<h2 id="beer_title"><?=$beerdoc->name?></h2>

<div id="description"><?=$beerdoc->description?></div>

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

<?php print file_get_contents("../html/footer.html"); ?>
