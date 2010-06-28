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
	print json_encode($msg)."\n";
}

function email_exists($email)
{
	global $oak;

	/*
	This isn't foolproof. The CouchDB view is cached and with multiple CouchDB servers,
	there's replication latency. So we store it in memcached for 24 hours too.
	*/

	// First, check memcached.
	if ($oak->memcached_get('email:'.$email)!==false)
		return true; // Email already exists
	
	// Second, check CouchDB
	$results=new stdClass;
	if ($oak->get_view('user/email?key=%22'.urlencode($email).'%22',&$results)==false)
		return false; // Email does not exist
		
	/*
		Note: the email address could still exist in the db. We aren't perfect.
	*/

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
		if ($oak->memcached_set('email:'.$email,$user_doc,86400)===false) {
			$result=$oak->memcached_get_result();
			$oak->log('Failed to put new user in memcached. result='.$result['text'].' ('.$result['code'].')',OAK::LOGPRI_ERR);
			/* 
			Don't really fail, the memcached version is there as a backup to the CouchDB document.
			The memcached version doesn't have to be there, but without it, accounts with duplicate 
			email addresses can be created because the CouchDB view is cached and because the CouchDB
			replication may not yet be updated.
			*/
		}
		
		// Announce a view change (so it gets uncached soon)
		$view_url='/'.$oak->get_config_info()->couchdb->database.'/_design/user/_view/email?key=%22'.urlencode($email).'%22';
		$oak->log('viewchanges:'.$view_url);
		$oak->broadcast_msg('viewchanges',$view_url);

		/*
			Indicate success. Don't log the user in automatically, though. They must issue a separate login.
		*/
		header("HTTP/1.0 200 Login successful");
		header("Content-Type: application/json;charset=utf-8");

		$reply=array(
			'success' => true,
			'userid' => $userid,
			'viewchanges' => $view_url,
		);
	
		print json_encode($reply)."\n";
	}
}

?>