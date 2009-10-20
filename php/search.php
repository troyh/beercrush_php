<?php
require_once('beercrush/oak.class.php');

function my_var_dump($data)
{
	print "<pre>";
	var_dump($data);
	print "</pre>";
}

print file_get_contents("../html/header.html");

$oak=new OAK;
$results=$oak->query($_GET['q']);
// my_var_dump($results);
?>

<div id="searchresults">
<h3>Results</h3>
<?php foreach ($results->response->docs as $doc) { ?>
	<div>
		<a href="/<?=str_replace(':','/',$doc->id)?>"><?=$doc->name?></a>
	</div>
<?php } ?>
</div>

<?php
print file_get_contents("../html/footer.html");
?>