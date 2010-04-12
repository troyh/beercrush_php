<?php
header("Cache-Control: no-cache");
require_once 'OAK/oak.class.php';

function logout_failure($status_code,$reason='')
{
	$msg=array(
		'success' => false,
		'reason' => $reason,
	);
	
	header("HTTP/1.0 $status_code $reason");
	header("Content-Type: application/javascript");
	print json_encode($msg)."\n";
}

/*
	Log the user out.
*/
$oak=new OAK();

$oak->memcached_delete('loginsecret:'.$oak->get_user_id());
header("HTTP/1.0 200 OK");

?>