<?php 
require_once('beercrush/beercrush.php');

$places=BeerCrush::api_doc($BC->oak,'/places');

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
<?php
foreach ($places->$page as $place) :
	$place=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($place->id));
?>
	<div>
		<a href="/<?=BeerCrush::docid_to_docurl($place->id)?>"><?=$place->name?></a>
		<?=$place->address->city?>, 
		<?=$place->address->state?>
		<?=$place->address->country?>
	</div>
<?php
endforeach;
?>
</div>

<h2>Add a Place</h2>

<p>
Can't find a place? Add it here:
</p>
	
<form id="new_place_form" method="post" action="/api/place/edit">
	<input type="text" name="name" value="" />
	<input type="submit" value="Add" />
	<div id="new_place_msg"></div>
</form>

<script type="text/javascript">

function pageMain()
{
	$('#new_place_form').submit(function() {

		$('#new_place_form').ajaxError(function(evt,xhr,options,exception) {
			if (options.url==$('#new_place_form').attr('action')) {
				$('#new_place_msg').text('Unable to add place');
			}
		});

		$.post($('#new_place_form').attr('action'),
			$('#new_place_form').serialize(),
			function(data) {
				$('#new_place_msg').html(data.name+' added. <a href="/'+data.id.replace(/:/g,'/')+'">Edit it</a>.');
			},
			'json'
		);
		
		return false;
	});
}

</script>

<?php 
include('../footer.php');
?>
