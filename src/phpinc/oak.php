<?php
require_once('beercrush/couchdb.php');
require_once('beercrush/oak.class.php');

// Validate GET/POST data

$oak=new OAK();

// error_reporting(E_ALL|E_STRICT);
if (!$oak->is_debug_on())
	set_error_handler('OAK_error_handler',E_ALL|($oak->is_debug_on()?E_NOTICE|E_STRICT:0));
// register_shutdown_function('OAK_shutdown_function');

try
{
	global $cgi_fields;
	$oak->load_cgi_fields(&$cgi_fields);

	if ($oak->get_missing_field_count($cgi_fields))
		throw new Exception($oak->get_missing_field_count($cgi_fields)." required field(s) missing.");

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
	
	$oak->log('Exception:'.$x->getMessage()."\nStack Trace:\n".$x->getTraceAsString());
	
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

function OAK_error_handler($errno,$errstr,$errfile,$errline,$errcontext)
{
	header("HTTP/1.0 500 Internal error");
	
	print <<<EOF
<html>
	<body>
		<pre>
EOF;

	switch ($errno) 
	{
	    case E_NOTICE:
	    case E_USER_NOTICE:
			print "Notice: $errfile($errline): $errstr ($errno)";
	        break;
	    case E_WARNING:
	    case E_USER_WARNING:
			print "Warning: $errfile($errline): $errstr ($errno)";
	        break;
	    case E_ERROR:
	    case E_USER_ERROR:
			print "Fatal: $errfile($errline): $errstr ($errno)";
	        break;
	    default:
			print "Unknown: $errfile($errline): $errstr ($errno)";
	        break;
    }

	print <<<EOF
		</pre>
	</body>
</html>
EOF;
	return TRUE; // Prevent PHP from doing the normal error handler
}

function OAK_shutdown_function()
{
	$error=error_get_last();
	if (!is_null($error))
	{
		$error_types=array(
			'E_ERROR'		  => 'ERROR',
			'E_CORE_ERROR'    => 'CORE_ERROR',
			'E_COMPILE_ERROR' => 'COMPILE_ERROR',
			'E_USER_ERROR'    => 'USER_ERROR',
		);
		if (isset($error_types[$error['type']]))
			print $error_types[$error['type']].':';
		print $error['message'];
	}
}

?>
