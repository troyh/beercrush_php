<?php
require_once 'beercrush/oak.class.php';

function logout_failure($status_code,$reason='')
{
	$msg=array(
		'success' => false,
		'reason' => $reason,
	);
	
	header("HTTP/1.0 $status_code $reason");
	header("Content-Type: application/javascript");
	print json_encode($msg);
}

/*
	Log the user out.
*/
$oak=new OAK();
 if ($oak->login_is_trusted()==false)
{
	logout_failure(401,'Unauthorized');
}
else
{
	$user_doc=new OAKDocument('');
	if ($oak->get_document('user:'.$oak->get_user_id(),&$user_doc)!==true)
	{
		logout_failure(401,'Unauthorized');
	}
	else
	{
		unset($user_doc->secret);
		if ($oak->put_document($user_doc->getID(),$user_doc)!==true)
		{
			logout_failure(500,'Internal Error');
		}
		else
		{
			header("HTTP/1.0 200 OK");
		}
	}
}

?>