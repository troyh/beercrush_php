<?php
require_once 'beercrush/oak.class.php';

$cgi_fields=array(
	"beer_id"					=> array(flags=>OAK::FIELDFLAG_REQUIRED|OAK::FIELDFLAG_CGIONLY,type=>OAK::DATATYPE_TEXT)
);

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
		// TODO: verify that it's a JPEG
		
		$filename=uniqid('',FALSE).'.jpg';
		$uploadfile = $oak->get_file_location('WWW_DIR').'/uploads/'.$filename;

		$info=array(
			'user' => $oak->get_user_id(),
			'id' => $oak->get_cgi_value('beer_id',&$cgi_fields),
			'fetchurl' => '/uploads/'.$filename,
			'filename' => $filename,
			'hostname' => php_uname('n'),
			'timestamp' => time(),
			'type' => 'photo'
		);
		$json_info=json_encode($info);
		
		// Make info file as a backup (used in the event the queue is destroyed for any reason)
		$infofile = $oak->get_file_location('WWW_DIR').'/uploads/'.$filename.'.info';

		if (@move_uploaded_file($_FILES['photo']['tmp_name'], $uploadfile)===FALSE) 
		{
			header("HTTP/1.0 500 Unable to save photo");
		}
		else if (file_put_contents($infofile,$json_info)===FALSE)
		{
			header("HTTP/1.0 500 Unable to save photo info");
		}
		else if ($oak->put_queue_msg('uploads',$json_info)===FALSE) // Add the info to the queue
		{
			// We won't fail for this because we can (and should) scan the uploads directories 
			// on all the servers periodically anyway.
			// header("HTTP/1.0 500 Unable to queue upload");
		}
	}
}

require_once 'beercrush/oak.php';


?>