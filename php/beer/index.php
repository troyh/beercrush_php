<?php
	$beers=json_decode(file_get_contents("http://localhost/api/beers"));
	
	if (empty($_GET['letter']))
		$page='#';
	else
		$page=$_GET['letter'];

	header('Content-type: text/html; charset=utf-8');
	print file_get_contents("../../html/header.html");
?>

<h1>Beers</h1>

<div id="letters">
<?php foreach ($beers as $letter=>$data) { ?>
	<a href="/beers/<?=$letter?>"><?=$letter?></a>
<?php } ?>
</div>
	
<div id="beers_list">
<?php foreach ($beers->$page as $beer) { 
	$brewery=json_decode(file_get_contents('http://localhost/api/'.str_replace(':','/',$beer->brewery_id)));
?>
	<div><a href="/<?=str_replace(':','/',$beer->id)?>"><?=$beer->name?></a> by <a href="/<?=str_replace(':','/',$beer->brewery_id)?>"><?=$brewery->name?></a></div>
<?php } ?>
</div>

<script type="text/javascript">

function BeerCrushMain()
{
}

</script>

<?php 
	print file_get_contents("../../html/footer.html");
?>
