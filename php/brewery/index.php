<?php 
require_once('beercrush/oak.class.php');

$oak=new OAK;
$breweries=json_decode(file_get_contents($oak->get_config_info()->api->base_uri.'/breweries'));
	
if (empty($_GET['letter']))
	$page='#';
else
	$page=$_GET['letter'];

header('Content-type: text/html; charset=utf-8');
print file_get_contents("../../html/header.html");
?>

<h1>Breweries</h1>

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
	// $.getJSON("/json/breweries",null,function(data,textStatus) {
	// 	for (var letter in data)
	// 	{
	// 		$("#letters").append("<a href=\"/brewery/?letter="+letter+"\">"+letter+"</a>");
	// 	}
	// 	
	// 	jQuery.each(data[getUrlVars()['letter']],function(k,v) {
	// 		$("#brewery_list").append("<div><a href=\"/"+v.id.replace(/:/,'/brewery.html?id=')+"\">"+v.name+"</a></div>");
	// 	})
	// 
	// 	
	// });
}

</script>

<?php 
	print file_get_contents("../../html/footer.html");
?>
