<?php
require_once('beercrush/beercrush.php');
// print_r($_GET);exit;
$locations=new stdClass;

$key=urlencode('["'.$_GET['country'].'","'.$_GET['state'].'"]');
$endkey=urlencode('["'.$_GET['country'].'","'.$_GET['state'].'\\\\uFFFF"]');
$view_url='location/all?group_level=3&inclusive_end=true&startkey='.$key.'&endkey='.$endkey;
// print $view_url;exit;
$BC->oak->get_view($view_url,&$locations);
// print_r($locations);exit;

include('../header.php');
?>

<a href="../../">All</a> &gt; 
<a href="../"><?=$locations->rows[0]->key[0]?></a> &gt;
<?=$locations->rows[0]->key[1]?>

<h2>Browse by Cities:</h2>
<h1><?=$locations->rows[0]->key[1]?></h1>

<ul id="loclist">
<?php foreach ($locations->rows as $location):?>
	<li><a href="./<?=$location->key[2]?>/"><?=$location->key[2]?></a> <span>(<?=$location->value?>)</span></li>
<?php endforeach; ?>
</ul>
<?php
include('../footer.php');
?>