<?php
header('Cache-Control: no-cache');
require_once('beercrush/beercrush.php');

if (empty($_POST['email'])) {
	header('HTTP/1.0 405 Missing email');
	print "\n";
	exit;
}

$email=$_POST['email'];
$password=get_password($email);
if (is_null($password)) {
	header('HTTP/1.0 404 Password not found');
	print "\n";
	exit;
}

$BC->oak->broadcast_msg('sendmail',array(
	'template' => 'password_reminder',
	'email' => $email,
	'password' => $password,
));

print "\n";

function get_password($email) {
	global $BC;
	
	$docid=null;
	if (($user_doc=$BC->oak->memcached_get('email:'.$email))===false) {
		$results=new stdClass;
		if ($BC->oak->get_view('user/email?key=%22'.urlencode($email).'%22',&$results)===false)
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
		
	return $user_doc->password;
}

?>