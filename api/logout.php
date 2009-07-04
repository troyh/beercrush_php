<?php
require_once 'beercrush/oak.class.php';

$conf_file="/etc/BeerCrush/json.conf";

/*
	Log the user out.
*/

$oak=new OAK;
$oak->logout();

?>