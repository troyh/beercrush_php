<?php
header("Cache-Control: no-cache");
require_once 'OAK/oak.class.php';

/*
	Take email and password/MD5 CGI vars and validate them against the user db.
*/

function login_failure($status_code,$reason='')
{
	$msg=array(
		'success' => false,
		'reason' => $reason,
	);
	
	header("HTTP/1.0 $status_code $reason");
	header("Content-Type: application/javascript");
	print json_encode($msg)."\n";
	exit;
}

function get_cgi_value($name) {
	if (!empty($_POST[$name]))
		return $_POST[$name];
	if (!empty($_GET[$name]))
		return $_GET[$name];
	return null;
}

$email=get_cgi_value('email');
$password=get_cgi_value('password');
$md5=get_cgi_value('md5');

if (is_null($md5) && !is_null($password)) {
	$md5=md5($password);
}
	
if (is_null($email) || is_null($md5)) {
	login_failure(400,'email and password/MD5 are required'); // Create failed
}
else {
	$docid=null;
	
	$oak=new OAK();
	if (($user_doc=$oak->memcached_get('email:'.$email))===false) {
		$results=new stdClass;
		if ($oak->get_view('user/email?key=%22'.urlencode($email).'%22',&$results)===false)
		{
			login_failure(500,'Internal error');
		}
		else
		{
			if (count($results->rows)>1)
				$oak->log(count($results->rows).' accounts with email address '.$email);
			
			/*
				Find the user doc for this email/password combo.
			
				In the event we have more than one user doc with the specified email address,
				we'll pick one where the password matches and use it.

				TODO: build a periodic scanner that removes such duplicates
			*/
			
			foreach ($results->rows as $row)
			{
				if ($row->key===$email) {
					if ($row->value[1]===$md5 || md5($row->value[0])===$md5) {
						$docid=$row->id;
						break;
					}
				}
			}

			if (is_null($docid)) {
				if (count($results->rows)) {
					$oak->log('failed login attempt (from couchdb):'.$email);
					login_failure(403,'Login failed');
				}
				else {
					$oak->log('No user with email:'.$email);
					login_failure(405,'email does not exist');
				}
			}
		}
	}
	else {
		$docid='user:'.$user_doc->userid;
	}

	$user_doc=new OAKDocument('');
	if ($oak->get_document($docid,&$user_doc)!==true) {
		login_failure(500,'Internal error');
	}
	else if (empty($user_doc->md5) && md5($user_doc->password)!==$md5) {
		$oak->log('failed login attempt (from memcached):'.$email);
		login_failure(403,'Login failed');
	}
	else if ($user_doc->md5!==$md5) {
		$oak->log('failed login attempt (from memcached):'.$email.' md5='.$md5.' should be='.$user_doc->md5);
		login_failure(403,'Login failed');
	}
	else {
		// Create another secret
		$secret=rand();
		$oak->memcached_set('loginsecret:'.$user_doc->userid,$secret);

		// Make and return userkey
		$usrkey=md5($user_doc->userid.$secret.$_SERVER['REMOTE_ADDR']);

		/*
			Indicate success.
		*/
		$oak->log('Successful login:'.$user_doc->userid);

		header("HTTP/1.0 200 OK");
		header("Content-Type: application/json; charset=utf-8");

		$answer=array(
			'userid'=>$user_doc->userid,
			'usrkey'=>$usrkey,
		);
	
		if (!empty($user_doc->name))
			$answer['name']=$user_doc->name;
		else
			$answer['name']="Anonymous";
		if (!empty($user_doc->avatar))
			$answer['avatar']=$user_doc->avatar;
		
		print json_encode($answer)."\n";

	}
}

?>