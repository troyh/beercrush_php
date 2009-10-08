<?php
require_once 'beercrush/oak.class.php';

function login_create_failure($reason='')
{
	$msg=array(
		'success' => false,
		'reason' => $reason,
	);
	
	header("Content-Type: application/javascript");
	print json_encode($msg);
}


/*
	Take userid and password CGI vars and create a login
*/

$oak=new OAK();

$userid=null;
$password=null;

if (empty($_POST['userid']) || empty($_POST['password']))
{
	if (empty($_GET['userid']) || empty($_GET['password']))
	{
	}
	else
	{
		$userid=$_GET['userid'];
		$password=$_GET['password'];
	}
}
else
{
	$userid=$_POST['userid'];
	$password=$_POST['password'];
}

if (is_null($userid) || is_null($password))
{
	header("HTTP/1.0 420 userid and password are required");
	$oak->logout(); // Clears login cookies
	login_create_failure('userid and password are required'); // Create failed
}
else if ($oak->login_create($userid,$password)!==true)
{
	header("HTTP/1.0 520 Userid already exists");
	$oak->logout(); // Clears login cookies
	login_create_failure('userid already exists'); // Create failed
}
else
{
	$user_key="";
	if ($oak->login($userid,$password,$user_key)!==true)
	{
		/* 
			Silently ignore this. The account was created, which is the job of this request, they 
			just aren't logged in automatically.
		*/
	}

	/*
		Indicate success. Don't log the user in automatically, though. They must issue a separate login.
	*/
	header("Content-Type: application/javascript");

	$reply=array(
		'success' => true,
		'userid' => $userid,
		'usrkey' => $user_key,
	);
	
	print json_encode($reply);
}

?>