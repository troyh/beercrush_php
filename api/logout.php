<?php
require_once 'beercrush/oak.class.php';

/*
	Log the user out.
*/

$oak=new OAK('/etc/BeerCrush/json.conf');
$oak->logout();

?>