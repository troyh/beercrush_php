<?php
require_once('OAK/oak.class.php');

$cgi_fields=array(
	"q"	=> array(type=>OAK::DATATYPE_TEXT),
	"doctype"	=> array(type=>OAK::DATATYPE_TEXT),
	"start"	=> array(type=>OAK::DATATYPE_INT, min => 0),
);

function oakMain($oak)
{
	global $cgi_fields;
	if ($oak->cgi_value_exists('q',$cgi_fields))
	{
		
		$params=array(
			'wt' => 'json',
			'rows' => 20,
			'start' => $oak->cgi_value_exists('start',$cgi_fields)?$oak->get_cgi_value('start',&$cgi_fields):0,
			'sort' => 'score desc',
			'qt' => 'dismax',
			'q' => $oak->get_cgi_value('q',$cgi_fields),
		);
		
		if ($oak->cgi_value_exists('doctype',$cgi_fields)) {
			switch ($oak->get_cgi_value('doctype',&$cgi_fields)) {
				case 'location':
				case 'style':
				default:
					$params['fl']='id,name,score';
					$params['fq']='doctype:'.$oak->get_cgi_value('doctype',&$cgi_fields);
					break;
			}
		}
		
		// Pick a node
		$node=$oak->get_config_info()->solr->nodes[rand()%count($oak->get_config_info()->solr->nodes)];
		$url='http://'.$node.$oak->get_config_info()->solr->url.'/select/?';
		
		// Construct Solr query URL
		foreach ($params as $k=>$v)
		{
			$url.=$k.'='.urlencode($v).'&';
		}
		// print "Solr URL:$url\n";//exit;

		$ch=curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		$results=json_decode(curl_exec($ch));
		if (is_null($results))
		{
			header('HTTP/1.0 500 Internal error');
			print "Internal error";
			exit;
		}
		
		// If we have results, broadcast to searchhits
		if (count($results->response->docs)) {
			$searchhits=array(
				'query_params' => $params,
			);
			$oak->broadcast_msg('searchhits',$searchhits);
		}

		// // Add in brewery info for beers
		// foreach ($results->response->docs as &$doc)
		// {
		// 	if (substr($doc->id,0,5)=='beer:')
		// 	{
		// 		list($type,$brewery_id,$beer_id)=explode(':',$doc->id);
		// 		$brewery_doc=new OAKDocument('');
		// 		if ($oak->get_document('brewery:'.$brewery_id,&$brewery_doc)===true)
		// 		{
		// 			$doc->brewery=new stdClass;
		// 			$doc->brewery->name=$brewery_doc->name;
		// 			$doc->brewery->id=$brewery_doc->_id;
		// 		}
		// 	}
		// }
		
		header("Content-Type: application/json; charset=utf-8");
		print json_encode($results)."\n";
	}
}

require_once 'OAK/oak.php';
