<?php
require_once('beercrush/oak.class.php');

function my_var_dump($data)
{
	print "<pre>";
	var_dump($data);
	print "</pre>";
}

header('Content-type: text/html; charset=utf-8');
print file_get_contents("../html/header.html");

switch ($_GET['dt'])
{
case 'beers':
	$doctypes=array('beer','brewery');
	break;
case 'place':
	$doctypes=array('place');
	break;
default:
	$doctypes=null;
	break;
}

$oak=new OAK;
$results=$oak->query($_GET['q'],true,$doctypes);
// my_var_dump($results);exit;
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
print file_get_contents("../html/footer.html");
?>