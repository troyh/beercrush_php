<?php
require_once 'beercrush/oak.class.php';

$conf_file="/etc/BeerCrush/json.conf";

/*
	Take userid and password CGI vars and validate them against the user db.
*/

$oak=new OAK;
$user_key="";
if ($oak->login($_GET['userid'],$_GET['password'],$user_key)===false)
{
	// Login failed
	$oak->login_failure();
	exit;
}

/*
	Indicate success.
*/
$oak->login_success($_GET['userid'],$_GET['password'],$user_key);

?>