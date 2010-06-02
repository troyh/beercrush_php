<?php
require_once('beercrush/beercrush.php');

$locations=new stdClass;

$view_url='place/locations?group_level=2&startkey='.urlencode('["'.$_GET['country'].'"]');
// print $view_url;exit;
$BC->oak->get_view($view_url,&$locations);
// print_r($locations);exit;

include('../header.php');
?>

<a href="./">All locations</a> &gt;
<?=$locations->rows[0]->key[0]?>
<h1>Locations</h1>

<ul>
<?php foreach ($locations->rows as $location):?>
	<li><a href="./<?=$location->key[1]?>/"><?=$location->key[1]?></a> (<?=$location->value?>)</li>
<?php endforeach; ?>
</ul>
<?php
include('../footer.php');
?>