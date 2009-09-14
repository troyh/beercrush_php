<?php
require_once 'beercrush/oak.class.php';

$cgi_fields=array();

function oakMain($oak)
{
	global $cgi_fields;

	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		$oak->request_login();
	}
	else if ($_FILES['photo']['size']==0)
	{
		header("HTTP/1.0 500 Zero-length photo upload");
	}
	else
	{
		$file = $oak
		$uploadfile = $oak->get_file_location('WWW_DIR').'/uploads/'.$file;

		if (@move_uploaded_file($_FILES['photo']['tmp_name'], $uploadfile)===FALSE) 
		{
			header("HTTP/1.0 500 Upload failed");
		}
		else
		{
			/*
			
			Should we queue the upload info or put it in the database? I'd prefer the queue, but it's
			not fault-tolerant because if the queue daemon dies or the machine itself dies or goes away,
			the photo is orphaned.
			
			A solution to that is to store the data (user info, place ID, etc.) in a metafile with the
			image file and a regular cleanup process could scan all the web servers for any forgotten
			uploads and process them. This is just to avoid storing the info in the database, so maybe
			avoiding the database is unnecessary.
			
			Of course, the database could die as well and if it didn't replicate to the other databases
			servers, we have the same orphan problem.
			
			*/
		}
	}
}

require_once 'beercrush/oak.php';


?>