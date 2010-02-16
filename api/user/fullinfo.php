<?php
// This page should never be cached because each request takes the user's
// cookies into account. If it's cached, one user can see another user's
// fullinfo.

header("Cache-Control: no-cache");
require_once('beercrush/oak.class.php');

$oak=new OAK;
if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
{
	$oak->request_login();
}
else
{
	$user_id=$oak->get_user_id();
	$oak->get_document('user:'.$user_id,&$userdoc);

	header('Content-Type: application/json; charset=utf-8');
	unset($userdoc->_id);
	unset($userdoc->_rev);
	unset($userdoc->password);
	unset($userdoc->secret);
	print json_encode($userdoc);
}

?>