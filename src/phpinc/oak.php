<?php
require_once('beercrush/couchdb.php');
require_once('beercrush/oak.class.php');

// Validate GET/POST data

$oak=new OAK();

try
{
	global $cgi_fields;
	$oak->load_cgi_fields(&$cgi_fields);

	if ($oak->get_missing_field_count($cgi_fields))
		throw new Exception($oak->get_missing_field_count()." required field(s) missing.");

	$invalid_fields=$oak->get_invalid_fields($cgi_fields);
	if (count($invalid_fields))
	{
		$msg=count($invalid_fields)." invalid value".(count($invalid_fields)==1?'':'s').": ".join(array_keys($invalid_fields),', ');
		// foreach ($invalid_fields as $name=>$attribs)
		// {
		// 	$msg.='('.$attribs['validate_failure'].')';
		// }
		throw new Exception($msg);
	}
	
	oakMain($oak);
}
catch(Exception $x)
{
	header("HTTP/1.0 400 Exception");
	
	$xmlwriter=new XMLWriter;
	$xmlwriter->openMemory();
	$xmlwriter->startDocument();
	
	$xmlwriter->startElement('div');
	$xmlwriter->writeAttribute('class','exception_msg');
	$xmlwriter->text($x->getMessage());
	
	if ($oak->is_debug_on())
	{
		// Dump call stack
		$xmlwriter->startElement('div');
		$xmlwriter->writeAttribute('id','exception_callstack');
		$xmlwriter->startElement('pre');
		$xmlwriter->text($x->getTraceAsString());
	
		// print '<div id="exception_callstack"><pre>'.$x->getTraceAsString().'</pre></div>';

		// $trace=$x->getTrace();
		// foreach ($trace as $call)
		// {
		// 	print '<div>'.$call['file']." (".$call['line']."):".$call['function'].'(';
		// 	foreach ($call['args'] as $arg)
		// 	{
		// 		switch (gettype($arg))
		// 		{
		// 		case "object":
		// 			// print "Object:";var_dump($arg);
		// 			print get_class($arg);
		// 			break;
		// 		case "string":
		// 			print $arg;
		// 			break;
		// 		default:
		// 			print gettype($arg);
		// 		}
		// 	}
		// 	print ')</div>';
		// }
	}

	$xmlwriter->endDocument();
	print $xmlwriter->outputMemory();
}

?>
