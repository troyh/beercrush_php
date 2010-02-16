<?php
header('Cache-Control: no-cache');
require_once 'beercrush/oak.class.php';

$cgi_fields=array(
	"user_id"			=> array(type=>OAK::DATATYPE_TEXT,flags=>OAK::FIELDFLAG_REQUIRED,minlen=>36,maxlen=>41),
	"name"				=> array(type=>OAK::DATATYPE_TEXT,minlen=>3),
	"aboutme"			=> array(type=>OAK::DATATYPE_TEXT,minlen=>1),
	"avatar"			=> array(type=>OAK::DATATYPE_TEXT,validatefunc=>validate_avatar_url),
);

function validate_avatar_url($name,$value,$attribs,$converted_value,$oak)
{
	return parse_url($converted_value)!==FALSE;
}

function oakMain($oak)
{
	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		$oak->request_login();
	}
	else
	{
		global $cgi_fields;
		
		$user_id=$oak->get_cgi_value('user_id',$cgi_fields);
		if (preg_match('/^user:/',$user_id))
			$user_id=substr($user_id,5);
			
		// Users can only edit their own info
		if ($user_id != $oak->get_user_id())
		{
			header('HTTP/1.0 403 Permission denied');
			exit;
		}
		
		$userdoc=new OAKDocument('');
		if ($oak->get_document('user:'.$user_id,&$userdoc)!==true)
		{
			header('HTTP/1.0 404 User not found');
			exit;
		}
		
		if ($oak->cgi_value_exists('avatar',$cgi_fields))
		{
			$avatar=$oak->get_cgi_value('avatar',$cgi_fields);
			if (empty($avatar))
				unset($userdoc->avatar);
			else
				$userdoc->avatar=$avatar;
		}
		
		// Give it this request's edits
		$oak->assign_cgi_values(&$userdoc,$cgi_fields);

		// Store in db
		if ($oak->put_document($userdoc->getID(),$userdoc)!==true)
		{
			header("HTTP/1.0 500 Save failed");
			exit;
		}

		$oak->log('Edited: user:'.$user_id);

		$user=new stdClass;
		$user->id=$userdoc->_id;
		$user->email=$userdoc->email;
		$user->aboutme=$userdoc->aboutme;
		if (!empty($userdoc->name))
			$user->name=$userdoc->name;
		if (!empty($userdoc->avatar))
			$user->avatar=$userdoc->avatar;

		header("Content-Type: application/json; charset=utf-8");
		print json_encode($user);
	}
}

require_once 'beercrush/oak.php';

?>