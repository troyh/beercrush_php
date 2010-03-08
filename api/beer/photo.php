<?php
header("Cache-Control: no-cache");

require_once 'OAK/oak.class.php';

$cgi_fields=array(
	"beer_id"					=> array(flags=>OAK::FIELDFLAG_REQUIRED|OAK::FIELDFLAG_CGIONLY,type=>OAK::DATATYPE_TEXT)
);

function oakMain($oak)
{
	global $cgi_fields;

	if ($_FILES['photo']['size']==0)
	{
		header("HTTP/1.0 500 Zero-length photo upload");
	}
	else
	{
		// TODO: verify that it's a JPEG
		// TODO: put the photo in a temporary location
		
		$uuid=$oak->create_uuid();
		$filename=$uuid.'.jpg';
		$path='/var/local/BeerCrush/images/'.chunk_split(substr($uuid,0,8),2,'/');
		$uploadfile=$path.$filename;
		
		mkdir($path,0775,true);
		
		$info=array(
			'user' => $oak->get_user_id(),
			'id' => $oak->get_cgi_value('beer_id',&$cgi_fields),
			'uuid' => $uuid,
			'url' => '/api/image/'.$filename,
			'filename' => $uploadfile,
			'timestamp' => time(),
			'type' => 'newphoto'
		);
		$json_info=json_encode($info);
		
		if (@move_uploaded_file($_FILES['photo']['tmp_name'], $uploadfile)===FALSE) 
		{
			header("HTTP/1.0 500 Unable to save photo");
			exit;
		}

		if ($oak->broadcast_msg('newphotos',$json_info)===FALSE) {
			// What to do?
		}

		$oak->log('Uploaded beer photo for beer '.$info['id'].' from user '.$info['user']);
		
		unset($info['fetchurl']);
		unset($info['hostname']);
		
		header('Content-Type: application/json; charset=utf-8');
		print json_encode($info);
	}
}

require_once 'OAK/oak.php';


?>