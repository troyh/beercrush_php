<?php
require_once('beercrush/beercrush.php');

function my_var_dump($data)
{
	print "<pre>";
	var_dump($data);
	print "</pre>";
}

switch ($_GET['dt'])
{
case 'beers':
	$doctypes=array('beer','brewery');
	break;
case 'place':
	$doctypes=array('place');
	break;
default:
	$doctypes=array();
	break;
}

$oak=new OAK(BeerCrush::CONF_FILE);
$url='search?q='.$_GET['q'].'&dataset='.join(' ',$doctypes);
$results=BeerCrush::api_doc($oak,$url);
// my_var_dump($results);exit;

include("header.php");
?>

<div id="searchresults">
<h3><?=$results->response->numFound?> Results</h3>
<?php foreach ($results->response->docs as $doc) { ?>
	<div>
		<a href="/<?=str_replace(':','/',$doc->id)?>"><?=$doc->name?></a>
	</div>
<?php } ?>
</div>

<?php
include("footer.php");
?>