<?php
require_once('beercrush/oak.class.php');
$oak=new OAK;
if ($oak->get_document('wishlist:'.$_GET['user_id'],&$wishlist)!==true)
{
	header('HTTP/1.0 400 No wishlist');
}
else
{
	unset($wishlist->_id);
	unset($wishlist->_rev);
	header('Content-Type: text/javascript; charset=utf-8');
	print json_encode($wishlist);
}

?>