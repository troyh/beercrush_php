<?php 
require_once('beercrush/oak.class.php');

if (isset($_GET['view']) && $_GET['view']==='date')
{
	$view="date";
}
else
{
	$view="name";
	if (empty($_GET['letter']))
		$page='#';
	else
		$page=$_GET['letter'];
}
	
$oak=new OAK;
$breweries=json_decode(file_get_contents($oak->get_config_info()->api->base_uri.'/breweries'));

include('../header.php');
?>

<h1>Breweries</h1>

<ul>
	<li><a href="./">By Name</a></li>
	<li><a href="./bydate/">By Date</a></li>
</ul>

<div id="letters">
<?php foreach ($breweries as $letter=>$data) { ?>
	<a href="/breweries/<?=$letter?>"><?=$letter?></a>
<?php } ?>
</div>
	
<div id="brewery_list">
<?php foreach ($breweries->$page as $brewery) { ?>
	<div><a href="/<?=str_replace(':','/',$brewery->id)?>"><?=$brewery->name?></a></div>
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
