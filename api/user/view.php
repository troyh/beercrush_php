<?php
require_once('beercrush/oak.class.php');

if (!preg_match('/^user:/',$_GET['user_id']))
	$user_id='user:'.$_GET['user_id'];
else
	$user_id=$_GET['user_id'];

$oak=new OAK;
$userdoc=new OAKDocument('');
if ($oak->get_document($user_id,&$userdoc)!==true)
{
	header('HTTP/1.0 404 User not found');
	exit;
}

/*
We don't just output the $userdoc because it has private info (email address, 
secret key, etc.) that we don't want to give out mistakenly. Because we
construct a new object to output, we won't accidentally allow new data added 
to the user document to automatically be displayed here. We have to explicitly
add it here if we want itout.
*/
$user=new stdClass;
$user->id=$userdoc->_id;
$user->meta->timestamp=$userdoc->meta->timestamp;
$user->aboutme=$userdoc->aboutme;
if (!empty($userdoc->name))
	$user->name=$userdoc->name;

if (!empty($userdoc->avatar))
	$user->avatar=$userdoc->avatar;

header("Content-Type: application/json; charset=utf-8");
print json_encode($user);

?>