<?php 
require_once('beercrush/oak.class.php');

$oak=new OAK;

$places=json_decode(file_get_contents($oak->get_config_info()->api->base_uri."/places"));

if (empty($_GET['letter']))
	$page='#';
else
	$page=$_GET['letter'];

include('../header.php');

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
include('../footer.php');
?>
