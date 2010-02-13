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

<h2>Add a brewery</h2>

<p>Don't see a brewery here? Add it:</p>

<form id="new_brewery_form" method="post" action="/api/brewery/edit">
	<input type="text" name="name" size="30" value="" />
	<input type="submit" value="Add" />
	<div id="new_brewery_msg"></div>
</form>

<script type="text/javascript">

function pageMain()
{
	$('#new_brewery_form').submit(function(){
		$('#new_brewery_msg').text('Adding...');
		$('#new_brewery_form').ajaxError(function(e,xhr,options,exception) {
			if (options.url=='/api/brewery/edit') {
				if (xhr.status==409) { // Duplicate beer
					$('#new_brewery_msg').html("There's already a brewery with that name.");
				}
			}
		});

		$.post($(this).attr('action'),$(this).serialize(),function(data,status,xhr){
			$('#new_brewery_msg').html(data.name+' added! <a href="/'+data.id.replace(/:/g,'/')+'">Edit it</a>');
		},'json');
		
		return false;
	});
}

</script>

<?php 
include('../footer.php');
?>
