<?php
require_once('beercrush/oak.class.php');

$cgi_fields=array(
	"q"	=> array(type=>OAK::DATATYPE_TEXT),
	"dataset"	=> array(type=>OAK::DATATYPE_TEXT),
	"start"	=> array(type=>OAK::DATATYPE_INT, min => 0),
);

function oakMain($oak)
{
	global $cgi_fields;
	if ($oak->cgi_value_exists('q',$cgi_fields))
	{
		$params=array(
			'fq' => '',
			'fl' => array('id,name,score'),
			'wt' => 'json',
			'rows' => 20,
			'qt' => 'dismax',
			'q' => urlencode($oak->get_cgi_value('q',$cgi_fields)),
			'start' => 0,
		);
		
		if ($oak->cgi_value_exists('dataset',$cgi_fields))
		{
			$dataset=$oak->get_cgi_value('dataset',$cgi_fields);
			if (!empty($dataset))
			{
				$doctypes=preg_split('/\s+/',$dataset);
				$params['fq']=urlencode('doctype:'.join(' or doctype:',$doctypes));
				
				if (in_array('place',$doctypes))
				{
					$params['fl'][]='placetype';
					$params['fl'][]='address_city';
					$params['fl'][]='address_state';
				}
			}
		}

		if ($oak->cgi_value_exists('start',$cgi_fields))
		{
			$params['start']=$oak->get_cgi_value('start',$cgi_fields);
		}
		
		$params['fl']=join(',',$params['fl']);

		// Pick a node
		$node=$oak->get_config_info()->solr->nodes[rand()%count($oak->get_config_info()->solr->nodes)];
		$url='http://'.$node.$oak->get_config_info()->solr->url.'/select/?';
		
		// Construct Solr query URL
		foreach ($params as $k=>$v)
		{
			$url.=$k.'='.$v.'&';
		}
		// print "Solr URL:$url";

		$ch=curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		$results=json_decode(curl_exec($ch));
		if (is_null($results))
		{
			header('HTTP/1.0 500 Internal error');
			exit;
		}

		// Add in brewery info for beers
		foreach ($results->response->docs as &$doc)
		{
			if (substr($doc->id,0,5)=='beer:')
			{
				list($type,$brewery_id,$beer_id)=explode(':',$doc->id);
				$brewery_doc=new OAKDocument('');
				if ($oak->get_document('brewery:'.$brewery_id,&$brewery_doc)===true)
				{
					$doc->brewery=new stdClass;
					$doc->brewery->name=$brewery_doc->name;
					$doc->brewery->id=$brewery_doc->_id;
				}
			}
		}
		
		header("Content-Type: application/json; charset=utf-8");
		print json_encode($results);
	}
}

require_once 'beercrush/oak.php';
