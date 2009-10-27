<?php
require_once('beercrush/oak.class.php');
$oak=new OAK;

if (substr($_GET['user_id'],0,5)==='user:')
	$user_id=substr($_GET['user_id'],5);
else
	$user_id=$_GET['user_id'];

if ($oak->get_document('wishlist:'.$user_id,&$wishlist)!==true)
{
	header('HTTP/1.0 400 No wishlist');
}
else
{
	$output=array(
		'items' => array(),
	);
	foreach ($wishlist->items as $item)
	{
		$doc=new stdClass;
		if ($oak->get_document($item->item_id,&$doc)===true)
		{
			$doc->beer_id=$doc->_id;
			unset($doc->_id);
			unset($doc->_rev);
			$output['items'][]=$doc;
		}
	}
	
	header('Content-Type: text/javascript; charset=utf-8');
	print json_encode($output);
}

?>