#!/usr/bin/php
<?php
require_once('beercrush/beercrush.php');
require_once('../listener.class.php');

$oak=new OAK(BeerCrush::CONF_FILE);
$oaklistener=new OAKListener($oak,$argv[1]);
$oaklistener->gimme_messages('message_callback');

function message_callback($oaklistener,$msg) {
	print_r($msg);
	print "\n";
}
?>