<?php
header('Cache-Control: no-cache');
require_once('beercrush/beercrush.php');

if (empty($_POST['email'])) {
	header('HTTP/1.0 405 Missing email');
	print "\n";
	exit;
}

$email=$_POST['email'];
$password=reset_password($email);

if (is_null($password)) {
	header('HTTP/1.0 406 Account not found');
	print "\n";
	exit;
}

$BC->oak->broadcast_msg('sendmail',array(
	'template' => 'password_reminder',
	'email' => $email,
	'password' => $password,
));

print "\n";

function reset_password($email) {
	global $BC;
	
	$docid=null;
	if (($user_doc=$BC->oak->memcached_get('email:'.$email))===false) {
		$results=new stdClass;
		if ($BC->oak->get_view('user/email?key=%22'.urlencode($email).'%22',&$results)===false)
			return null;

		if (count($results->rows)==0)
			return null;
			
		if (count($results->rows)>1)
			$BC->oak->log(count($results->rows).' accounts with email address '.$email,OAK::LOGPRI_WARN);
		
		/*
			Find the user doc for this email/password combo.
		
			In the event we have more than one user doc with the specified email address,
			we'll pick one where the password matches and use it.
		*/
		
		foreach ($results->rows as $row) {
			if ($row->key===$email) {
				$docid=$row->id;
				break;
			}
		}
	}
	else {
		$docid='user:'.$user_doc->userid;
	}
	
	if (is_null($docid))
		return null;

	$user_doc=new OAKDocument('');
	if ($BC->oak->get_document($docid,&$user_doc)!==true)
		return null;

	$password=random_password();
	$user_doc->md5=md5($password);
	if ($BC->oak->put_document($docid,$user_doc)!==true) 
		return null;
		
	if ($BC->oak->memcached_set('email:'.$email,$user_doc,86400)===false) {
		$result=$BC->oak->memcached_get_result();
		$BC->oak->log('Failed to put new user in memcached. result='.$result['text'].' ('.$result['code'].')',OAK::LOGPRI_ERR);
		/* 
		Don't really fail, the memcached version is there as a backup to the CouchDB document.
		The memcached version doesn't have to be there, but without it, accounts with duplicate 
		email addresses can be created because the CouchDB view is cached and because the CouchDB
		replication may not yet be updated.
		*/
	}

	// Announce a view change (so it gets uncached soon)
	$view_url='/'.$BC->oak->get_config_info()->couchdb->database.'/_design/user/_view/email?key=%22'.urlencode($email).'%22';
	$BC->oak->log('viewchanges:'.$view_url);
	$BC->oak->broadcast_msg('viewchanges',$view_url);

	return $password;
}

function random_password() {
	// alphabet isn't all letters, we remove some to avoid confusion with 1s, 0s, etc.
	$alphabet='abcdefghijkmnopqrstuvwxyzABCDEFGHJKMNPRSTUVWXYZ0123456789';
	$len=strlen($alphabet);
	$password=array();
	for ($i=0;$i<8;++$i) {
		$password[]=$alphabet[(rand()%$len)];
	}
	return join('',$password);
}

?>