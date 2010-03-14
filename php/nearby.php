<?php
require_once 'beercrush/beercrush.php';

if (!empty($_GET['lat']) && !empty($_GET['lon'])) {
	$nearbylist=$BC->docobj('nearby.fcgi?lat='.$_GET['lat'].'&lon='.$_GET['lon']);
}

include('./header.php');
?>

<h1>Places Nearby (<?=$nearbylist->count?>)</h1>

<?foreach ($nearbylist->places as $place):?>
<div>
	<div><a href="/<?=BeerCrush::docid_to_docurl($place->id)?>"><?=$place->name?></a></div>
</div>
<?endforeach;?>

<script type="text/javascript" charset="utf-8">

$.extend({
  getUrlVars: function(){
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  },
  getUrlVar: function(name){
    return $.getUrlVars()[name];
  }
});

function pageMain() {
	if ($.getUrlVar('lat')==undefined || $.getUrlVar('lon')==undefined) {
		if(navigator.geolocation) {
		    browserSupportFlag = true;
		    navigator.geolocation.getCurrentPosition(function(position) {
				window.location.href='/nearby?lat='+position.coords.latitude+'&lon='+position.coords.longitude;
		    }, function() {
		    });
		}
	}
}
	
</script>

<?php include("./footer.php"); ?>
