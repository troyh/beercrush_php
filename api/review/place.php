<?php
require_once('OAK/oak.class.php');

$cgi_fields=array(
	"place_id"				=> array(type=>OAK::DATATYPE_TEXT,minlen=>9),
	"user_id"				=> array(type=>OAK::DATATYPE_TEXT),
	"seqnum"				=> array(type=>OAK::DATATYPE_INT),
);

function oakMain($oak)
{
	global $cgi_fields;
	
	$reviews=new stdClass;

	if ($oak->cgi_value_exists('user_id',$cgi_fields))
	{
		$user_id=$oak->get_cgi_value('user_id',$cgi_fields);

		if ($oak->cgi_value_exists('place_id',$cgi_fields))
		{	// Get just the one review document
			$place_id=$oak->get_cgi_value('place_id',$cgi_fields);
			$review=new OAKDocument('');
			if ($oak->get_document('review:'.$place_id.':'.$user_id,&$review)!==true)
			{
				header('HTTP/1.0 400 No review');
				exit;
			}
			else
			{
				print json_encode($review);
				exit;
			}
		}
		else if ($oak->get_view('place_reviews/by_user_id?key=%22'.$user_id.'%22',$reviews)!==true)
		{
			header('HTTP/1.0 500 Internal error');
			exit;
		}
	}
	else if ($oak->cgi_value_exists('place_id',$cgi_fields))
	{
		$place_id=$oak->get_cgi_value('place_id',$cgi_fields);
		if ($oak->get_view('place_reviews/all?key=%22'.$place_id.'%22',$reviews)!==true)
		{
			header('HTTP/1.0 500 Internal error');
			exit;
		}
	}
	
	if ($oak->cgi_value_exists('seqnum',$cgi_fields))
		$seqnum=$oak->get_cgi_value('seqnum',$cgi_fields);
	else
		$seqnum=0;
	
	$output=array(
		'reviews' => array(),
	);

	$rows=array_slice($reviews->rows,$seqnum*20,20);
	foreach ($rows as $row)
	{
		$review=new OAKDocument('');
		$oak->get_document($row->id,$review);
		$output['reviews'][]=$review;
	}
	
	header('Content-Type: text/javascript; charset=utf-8');
	print json_encode($output);

}

require_once 'OAK/oak.php';

?>