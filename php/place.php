<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$place=new stdClass;
$oak->get_document('place:'.$_GET['id'],$place);
// var_dump($place);exit;

include("header.php");
?>

<h1><?=$place->name?></h1>
<div>Type: <?=$place->placetype?></div>

<div id="address">
	<div id="street"><?=$place->address->street?></div>
	<span id="city"><?=$place->address->city?></span>, <span id="state"><?=$place->address->state?></span> <span id="country"><?=$place->address->country?></span>
</div>

<div id="phone"><?=$place->phone?></div>
<a id="url" href="<?=$place->uri?>"><?=$place->uri?></a>

<div><?=$place->description?></div>

<h2>Beers</h2>
<div id="beerlist">
</div>

<?
include("footer.php");
?>