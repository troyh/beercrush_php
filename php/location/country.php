<?php
require_once('beercrush/beercrush.php');

$locations=new stdClass;

$view_url='location/all?group_level=2&startkey='.urlencode('["'.$_GET['country'].'"]');
// print $view_url;exit;
$BC->oak->get_view($view_url,&$locations);
// print_r($locations);exit;

include('../header.php');
?>

<a href="../">All</a> &gt;
<?=$locations->rows[0]->key[0]?>

<h2>Browse by States/Provinces:</h2><h1><?=$locations->rows[0]->key[0]?></h1>

<ul id="loclist">
<?php foreach ($locations->rows as $location):?>
	<li><a href="./<?=$location->key[1]?>/"><?=$location->key[1]?></a> <span>(<?=$location->value?>)</span></li>
<?php endforeach; ?>
</ul>
<?php
include('../footer.php');
?>