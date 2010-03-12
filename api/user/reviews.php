<?php
require_once('beercrush/beercrush.php');

if (empty($_GET['user_id'])) {
	header('HTTP/1.0 404 No user_id');
	exit;
}

$oak=new OAK(BeerCrush::CONF_FILE);
$viewdoc=new stdClass;
if ($oak->get_document('/_design/review/_view/by_user?key=%22'.$_GET['user_id'].'%22',&$viewdoc)!==true) {
	header('HTTP/1.0 404 User not found');
	exit;
}

$reviews=array();
foreach ($viewdoc->rows as $row) {
	$row->value->id=$row->value->_id;
	unset($row->value->_id);
	unset($row->value->_rev);
	$reviews[]=$row->value;
}

header("Content-Type: application/json; charset=utf-8");
$output=array(
	'meta' => array(
		'user_id' => $_GET['user_id'],
		'timestamp' => time(),
	),
	'reviews' => $reviews,
);
print json_encode($output);

?>
