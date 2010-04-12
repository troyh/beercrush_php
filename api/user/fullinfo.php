<?php
// This page should never be cached because each request takes the user's
// cookies into account. If it's cached, one user can see another user's
// fullinfo.

header("Cache-Control: no-cache");
require_once('OAK/oak.class.php');

$oak=new OAK;

$user_id=$oak->get_user_id();
$oak->get_document('user:'.$user_id,&$userdoc);

header('Content-Type: application/json; charset=utf-8');
unset($userdoc->_id);
unset($userdoc->_rev);
unset($userdoc->password);
unset($userdoc->secret);
unset($userdoc->{"@attributes"});

if (empty($userdoc->avatar))
	$userdoc->gravatar_url='http://www.gravatar.com/avatar/'.md5($userdoc->email).'.jpg';

print json_encode($userdoc);

?>