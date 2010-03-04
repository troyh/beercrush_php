#!/usr/bin/php
<?php
require_once('beercrush/beercrush.php');
require_once('../listener.class.php');

$oak=new OAK(BeerCrush::CONF_FILE);
$oaklistener=new OAKListener($oak,'testlistener');
$oaklistener->gimme_messages('message_callback');

function message_callback($oaklistener,$msg) {
	print "Message:";
	print_r($msg);
	print "\n";
}
?>