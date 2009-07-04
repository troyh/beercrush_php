<?php
require_once 'beercrush/oak.class.php';

$conf_file="/etc/BeerCrush/json.conf";

$oak=new OAK;

if (empty($_GET['userid']) || empty($_GET['password']))
{
	// Create failed
	$oak->login_create_failure('userid and password are required');
	exit;
}

/*
	Take userid and password CGI vars and create a login
*/

if ($oak->login_create($_GET['userid'],$_GET['password'])!==true)
{
	// Create failed
	$oak->login_create_failure();
	exit;
}

/*
	Indicate success.
*/
$oak->login_success();

?>