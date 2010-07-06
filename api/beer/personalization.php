<?php
header('Cache-Control: no-cache');
require_once 'beercrush/beercrush.php';

$user_id=$BC->oak->get_user_id();
if (empty($user_id)) {
	header("HTTP/1.0 404 Missing userid");
	print "\n";
	exit;
}

if (empty($_GET['beer_id'])) {
	header("HTTP/1.0 404 Missing beer_id");
	print "\n";
	exit;
}

// TODO: actually compute the predicted rating
$personalizations=array(
	'userid' => $user_id,
	'beer_id' => $_GET['beer_id'],
	'predictedrating' => ((rand() % 500 + 1) / 100), // for now, just a random rating, a real number between 1.00 and 5.00
);

header('Content-Type: application/json; charset=utf-8');
print json_encode($personalizations)."\n";

?>