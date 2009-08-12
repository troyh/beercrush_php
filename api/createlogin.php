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

	header("Content-Type: application/xml");
	print $xmlwriter->outputMemory();
}


/*
	Take userid and password CGI vars and create a login
*/

$oak=new OAK();

$userid=null;
$password=null;

if (empty($_POST['userid']) || empty($_POST['password']))
{
	if (empty($_GET['userid']) || empty($_GET['password']))
	{
		header("HTTP/1.0 420 userid and password are required");
		$oak->logout(); // Clears login cookies
		login_create_failure('userid and password are required'); // Create failed
	}
	else
	{
		$userid=$_GET['userid'];
		$password=$_GET['password'];
	}
}
else
{
	$userid=$_POST['userid'];
	$password=$_POST['password'];
}

if (is_null($userid) || is_null($password))
{
	header("HTTP/1.0 420 userid and password are required");
	$oak->logout(); // Clears login cookies
	login_create_failure('userid and password are required'); // Create failed
}
else if ($oak->login_create($userid,$password)!==true)
{
	header("HTTP/1.0 500 Login not created");
	$oak->logout(); // Clears login cookies
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
	$xmlwriter->writeElement('userid',$userid);
	$xmlwriter->endElement();
	$xmlwriter->endDocument();

	header("HTTP/1.0 200 Login created");
	header("Content-Type: application/xml");
	print $xmlwriter->outputMemory();
}

?>