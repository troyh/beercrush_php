<?php
require_once 'beercrush/oak.class.php';

/*
	Take email and password CGI vars and validate them against the user db.
*/

function login_failure($status_code,$reason='')
{
	$msg=array(
		'success' => false,
		'reason' => $reason,
	);
	
	header("HTTP/1.0 $status_code $reason");
	header("Content-Type: application/javascript");
	print json_encode($msg);
}



$email=null;
$password=null;

// TODO: use OAK's get_cgi_value() instead of $_GET/$_POST directly
if (empty($_GET['email']))
	$email=$_POST['email'];
else
	$email=$_GET['email'];

if (empty($_GET['password']))
	$password=$_POST['password'];
else
	$password=$_GET['password'];
	
if (is_null($email) || is_null($password))
{
	login_failure(400,'email and password are required'); // Create failed
}
else
{
	$oak=new OAK();
	$results=new stdClass;
	if ($oak->get_view('user/email?key=%22'.urlencode($email).'%22',&$results)===false)
	{
		login_failure(404,'email does not exist'); // Create failed
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

		$docid=null;
		foreach ($results->rows as $row)
		{
			if ($row->key===$email && $row->value===$password)
			{
				$docid=$row->id;
				break;
			}
		}
		
		$user_doc=new OAKDocument('');
	
		if (is_null($docid))
		{
			/*
				Login failed
			*/
			login_failure(403,'Login failed');
			$oak->log('failed login attempt:'.$email);
		}
		else if ($oak->get_document($docid,&$user_doc)!==true)
		{
			login_failure(500,'Internal error');
		}
		else
		{
			// Create another secret
			$user_doc->secret=rand();
		
			if ($oak->put_document($docid,$user_doc)!==true)
			{
				login_failure(500,'Internal error');
			}
			else
			{
				// Make and return userkey
				$usrkey=md5($user_doc->userid.$user_doc->secret.$_SERVER['REMOTE_ADDR']);

				/*
					Indicate success.
				*/
				$oak->log('Login:'.$userid);

				header("HTTP/1.0 200 OK");
				header("Content-Type: application/javascript");
			
				$answer=array(
					'userid'=>$user_doc->userid,
					'usrkey'=>$usrkey,
				);
				print json_encode($answer);
			
			}
		}
	}
}

?>