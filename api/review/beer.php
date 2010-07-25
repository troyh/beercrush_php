<?php
require_once('beercrush/beercrush.php');

$cgi_fields=array(
	"beer_id"				=> array(type=>OAK::DATATYPE_TEXT,minlen=>9),
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

		if ($oak->cgi_value_exists('beer_id',$cgi_fields))
		{	// Get just the one review document
			$beer_id=$oak->get_cgi_value('beer_id',$cgi_fields);
			$review=new OAKDocument('');
			if ($oak->get_document('review:'.$beer_id.':'.$user_id,&$review)!==true)
			{
				header('HTTP/1.0 404 No review');
				print "No review\n";
				exit;
			}
			else
			{
				header('Content-Type: application/json; charset=utf-8');
				$review->id=$review->_id;
				unset($review->_id);
				unset($review->_rev);
				print json_encode($review)."\n";
				exit;
			}
		}
		else if ($oak->get_view('beer_reviews/by_user?key=%22'.$user_id.'%22',$reviews)!==true)
		{
			header('HTTP/1.0 500 Internal error');
			print "Internal error\n";
			exit;
		}
	}
	else if ($oak->cgi_value_exists('beer_id',$cgi_fields))
	{
		$beer_id=$oak->get_cgi_value('beer_id',$cgi_fields);
		if ($oak->get_view('beer_reviews/all?key=%22'.$beer_id.'%22',$reviews)!==true)
		{
			header('HTTP/1.0 500 Internal error');
			print "Internal error\n";
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
		$review=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($row->id));
		$output['reviews'][]=$review;
	}

	header('Content-Type: application/json; charset=utf-8');
	print json_encode($output)."\n";

}

require_once 'OAK/oak.php';

?>