<?php
require_once 'beercrush/oak.class.php';

$conf_file="/etc/BeerCrush/json.conf";

/*
	Take userid and password CGI vars and validate them against the user db.
*/

$xmlwriter=new XMLWriter;
$xmlwriter->openMemory();
$xmlwriter->startDocument();

$oak=new OAK;
$user_key="";

// TODO: use OAK's get_cgi_value() instead of $_GET/$_POST directly
if (empty($_GET['userid']))
	$userid=$_POST['userid'];
if (empty($_GET['password']))
	$password=$_POST['password'];
	
if ($oak->login($userid,$password,$user_key)!==true)
{
	/*
		Login failed
	*/
	$oak->logout(); // Clears login cookies

	$xmlwriter->startElement('login');
	$xmlwriter->writeAttribute('ok','no');
	$xmlwriter->writeElement('reason','Incorrect userid and/or password');
	$xmlwriter->endElement();
}
else
{
	/*
		Indicate success.
	*/
	$xmlwriter->startElement('login');
	$xmlwriter->writeAttribute('ok','yes');
	$xmlwriter->writeElement('userid',$userid);
	$xmlwriter->writeElement('usrkey',$user_key);
	$xmlwriter->endElement();
}

$xmlwriter->endDocument();

header("Content-Type: application/xml");
print $xmlwriter->outputMemory();

?>