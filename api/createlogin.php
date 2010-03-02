<?php
header("Cache-Control: no-cache");
require_once 'OAK/oak.class.php';

$oak=new OAK();

function login_create_failure($status_code,$reason='')
{
	$msg=array(
		'success' => false,
		'reason' => $reason,
	);
	
	header("HTTP/1.0 $status_code $reason");
	header("Content-Type: application/javascript");
	print json_encode($msg);
}

function email_exists($email)
{
	global $oak;
	$results=new stdClass;
	if ($oak->get_view('user/email?key=%22'.urlencode($email).'%22',&$results)==false)
		return false;

	if (count($results->rows)>1)
		$oak->log(count($results->rows).' accounts with email address '.$email);
		
	return count($results->rows)?true:false;
}


/*
	Take email and password CGI vars and create a login
*/


$email=null;
$password=null;

if (empty($_POST['email']) || empty($_POST['password']))
{
	if (empty($_GET['email']) || empty($_GET['password']))
	{
	}
	else
	{
		$email=$_GET['email'];
		$password=$_GET['password'];
	}
}
else
{
	$email=$_POST['email'];
	$password=$_POST['password'];
}

if (is_null($email) || is_null($password))
{
	login_create_failure(400,'email and password are required'); // Create failed
}
else if (email_exists($email))
{
	login_create_failure(409,'email already exists'); // Create failed
}
else
{
	$userid=$oak->create_uuid();
	
	$user_doc=new OAKDocument('user');

	$user_doc->type='user';
	$user_doc->userid=$userid;
	$user_doc->email=$email;
	$user_doc->password=$password;

	if ($oak->put_document('user:'.$userid,$user_doc)!==true)
	{
		login_create_failure(500,'Account creation failed'); // Create failed
	}
	else
	{
		/*
			Indicate success. Don't log the user in automatically, though. They must issue a separate login.
		*/
		header("HTTP/1.0 200 Login successful");
		header("Content-Type: application/javascript");

		$reply=array(
			'success' => true,
			'userid' => $userid,
		);
	
		print json_encode($reply);
	}
}

?>