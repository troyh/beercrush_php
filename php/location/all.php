<?php
require_once('beercrush/beercrush.php');

$locations=new stdClass;
$BC->oak->get_view('place/locations?group_level=1',&$locations);
// print_r($locations);

include('../header.php');
?>

<ul>
<?php foreach ($locations->rows as $country):?>
	<li><a href="./<?=$country->key[0]?>/"><?=$country->key[0]?></a> (<?=$country->value?>)</li>
<?php endforeach; ?>
</ul>
<?php
include('../footer.php');
?>