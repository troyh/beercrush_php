<?php 
	header('Content-type: text/html; charset=utf-8');

	$places=json_decode(file_get_contents("http://localhost/api/places"));
	
	if (empty($_GET['letter']))
		$page='#';
	else
		$page=$_GET['letter'];

	print file_get_contents("../../html/header.html");
?>

<h1>Places</h1>

<div id="letters">
<?php foreach ($places as $letter=>$data) { ?>
	<a href="/places/<?=$letter?>"><?=$letter?></a>
<?php } ?>
</div>
	
<div id="place_list">
<?php foreach ($places->$page as $place) { ?>
	<div><a href="/<?=str_replace(':','/',$place->id)?>"><?=$place->name?></a></div>
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
