<?php
require_once 'beercrush/oak.class.php';

function login_create_failure($reason='')
{
	$xmlwriter=new XMLWriter;
	$xmlwriter->openMemory();
	$xmlwriter->startDocument();
	$xmlwriter->startElement('login');
	$xmlwriter->writeAttribute('created','no');
	$xmlwriter->writeElement('reason',$reason);
	$xmlwriter->endElement();
	$xmlwriter->endDocument();

	header("HTTP/1.0 201 Login not created");
	header("Content-Type: application/xml");
	print $xmlwriter->outputMemory();
}


$conf_file="/etc/BeerCrush/json.conf";

/*
	Take userid and password CGI vars and create a login
*/

$oak=new OAK;

if (empty($_GET['userid']) || empty($_GET['password']))
{
	login_create_failure('userid and password are required'); // Create failed
}
else if ($oak->login_create($_GET['userid'],$_GET['password'])!==true)
{
	login_create_failure(); // Create failed
}
else
{
	/*
		Indicate success. Don't log the user in automatically, though. They must issue a separate login.
	*/
	$xmlwriter=new XMLWriter;
	$xmlwriter->openMemory();
	$xmlwriter->startDocument();
	$xmlwriter->startElement('login');
	$xmlwriter->writeAttribute('created','yes');
	$xmlwriter->writeElement('userid',$_GET['userid']);
	$xmlwriter->endElement();
	$xmlwriter->endDocument();

	header("HTTP/1.0 200 Login created");
	header("Content-Type: application/xml");
	print $xmlwriter->outputMemory();
}

?>