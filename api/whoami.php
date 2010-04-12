<?php
header("Cache-Control: no-cache");
require_once 'OAK/oak.class.php';

/*
	Log the user out.
*/

$oak=new OAK();

$user_id=$oak->get_user_id();
$user_key=$oak->get_user_key();

$xmlwriter=new XMLWriter;
$xmlwriter->openMemory();

$xmlwriter->startDocument();
$xmlwriter->startElement('login');

$xmlwriter->writeElement('userid',$user_id);
$xmlwriter->writeElement('usrkey',$user_key);

$xmlwriter->endElement();
$xmlwriter->endDocument();

echo $xmlwriter->outputMemory();

?>