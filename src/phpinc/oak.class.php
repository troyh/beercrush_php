<?php
require_once('beercrush/couchdb.php');

class OAKDocument
{
	function __construct($type) 
	{
		$this->type=$type;
		$this->timestamp=time();
	}
	
	function __set($name,$val) 
	{
		switch ($name)
		{
		case "_id":
		case "_rev":
		case "type":
		case "timestamp":
		case "@attributes":
		default:
			$this->$name=$val; 
			break;
			// throw new Exception('Unsupported property:'.$name);
			// break;
		}
	}
	function __get($name)   { return $this->$name; }
	function __isset($name) { return isset($this->$name); }
	function __unset($name) { unset($this->$name); }
	
	function setID($id) 
	{
		if (empty($id))
			throw new Exception('empty id');
		$this->_id=$id;
	}
	
	function getID() 
	{ 
		if (!isset($this->_id))
			throw new Exception('id not set');
		return $this->_id; 
	}
};

class OAK
{
	// These are bit flags, so they should go 1,2,4,8,...
	const FIELDFLAG_REQUIRED=1;
	const FIELDFLAG_CGIONLY=2;

	// Data types
	const DATATYPE_INT=1;
	const DATATYPE_TEXT=2;
	const DATATYPE_FLOAT=3;
	const DATATYPE_MONEY=4;
	const DATATYPE_BOOL=5;
	const DATATYPE_OBJ=6;
	const DATATYPE_URI=7;
	const DATATYPE_PHONE=8;
	
	// Priorities for log()
	const LOGPRI_INFO=LOG_INFO;
	const LOGPRI_ERR=LOG_ERR;
	const LOGPRI_CRIT=LOG_CRIT;
	
	const CGI_NAME_SEP=':';

	private $config;

	function __construct($conf_file=null) 
	{
		if (is_null($conf_file))
		{
			// Read from environment
			$conf_file=getenv('OAKConfig');
		}
		// Read conf_file
		$conf_json=file_get_contents($conf_file);
		$this->config=json_decode($conf_json);
		
		// Open syslog
		openlog('OAK',LOG_ODELAY|LOG_CONS|LOG_PID,LOG_LOCAL0);
		if (!empty($_SERVER['REQUEST_URI']))
			$this->log_ident($_SERVER['REQUEST_URI']);
		else
			$this->log_ident($_SERVER['PHP_SELF']);
	}
	
	function __destruct() 
	{
		// Close syslog
		closelog();
	}
	
	/*
	 * User Credentials Functions
	 */
	
	function login_create($userid,$password)
	{
		$user_doc=new stdClass;
		if ($this->get_document('user:'.$userid,$user_doc)===true)
			return false; // User account already exists

		$user_doc->userid=$userid;
		$user_doc->password=$password;
		$user_doc->secret=rand();
		$user_doc->type='user';
		
		if ($this->put_document('user:'.$userid,$user_doc)!==true)
			return false; // Failed to store document (internal error)
			
		return true; // Login created
	}

	function get_user_id()
	{
		if (!strlen($_COOKIE['userid']))
			return null; // User is not logged in
		return $_COOKIE['userid'];
	}

	function get_user_key()
	{
		if (!strlen($_COOKIE['usrkey']))
			return null; // User is not logged in
		return $_COOKIE['usrkey'];
	}
	
	function is_debug_on()
	{
		return $this->config->debug==="yes";
	}
	
	public function request_login()
	{
		header("HTTP/1.0 420 Login required");
		print "Login required\n";
	}
	
	function login($userid,$password,&$usrkey)
	{
		$user_doc=new stdClass;
		if ($this->get_document('user:'.$userid,$user_doc)!==true)
		{
			header("HTTP/1.0 201 Login failed");
			return false;
		}

		// Verify password is correct
		if ($user_doc->password!==$password)
		{
			header("HTTP/1.0 201 Login failed");
			return false;
		}
		
		// Create another secret
		$user_doc->secret=rand();
		if ($this->put_document('user:'.$userid,$user_doc)!==true)
		{
			header("HTTP/1.0 201 Internal error");
			return false;
		}
		
		// Make and return userkey
		$usrkey=md5($userid.$user_doc->secret.$_SERVER['REMOTE_ADDR']);

		header("HTTP/1.0 200 Login successful");

		if (strlen($this->config->cookies->domain))
		{
			setcookie('userid',$userid,time()+$this->config->cookies->lifetime,'/',$this->config->cookies->domain);
			setcookie('usrkey',$usrkey,time()+$this->config->cookies->lifetime,'/',$this->config->cookies->domain);
		}
		else
		{
			setcookie('userid',$userid,time()+$this->config->cookies->lifetime,'/');
			setcookie('usrkey',$usrkey,time()+$this->config->cookies->lifetime,'/');
		}

		return true;
	}

	function login_is_trusted()
	{
		$user_doc=new stdClass;
		if ($this->get_document('user:'.$this->get_user_id(),$user_doc)!==true)
			return false;
		
		$correct_key=md5($this->get_user_id().$user_doc->secret.$_SERVER['REMOTE_ADDR']);
		if ($correct_key!==$this->get_user_key())
			return false;
			
		return true;
	}
	
	function logout()
	{
		if (strlen($this->config->cookies->domain))
		{
			setcookie('userid','',time()-86400,'/',$this->config->cookies->domain);
			setcookie('usrkey','',time()-86400,'/',$this->config->cookies->domain);
		}
		else
		{
			setcookie('userid','',time()-86400,'/');
			setcookie('usrkey','',time()-86400,'/');
		}
	}


	/*
	 * User Credentials Functions
	 */
	
	function get_database_name()
	{
		return $this->config->couchdb->database;
	}
	
	/*
	 * CGI Variable Functions
	 */
	
	private function validate_value_range($attribs)
	{
		if (!isset($attribs['min']) || !isset($attribs['max']) || is_null($attribs['min']) || is_null($attribs['max']))
			return true; // We're not supposed to check range values, assume OK
			
		if (gettype($attribs['min'])!=gettype($attribs['max']))
		{
			$attribs['validate_failure']='min/max types incompatible';
			return false; // App config settings are not correct
		}

		if ($attribs['min']>$attribs['max'])
		{
			$attribs['validate_failure']='min > max';
			return false; // App config settings are not correct
		}

		if (gettype($attribs['min'])!=gettype($attribs['converted_value']))
		{
			$attribs['validate_failure']='min/max and value types incompatible';
			return false; // Types are not correct
		}
			
		if ($attribs['min']<=$attribs['converted_value'] && $attribs['converted_value']<=$attribs['max'])
			return true;

		$attribs['validate_failure']='Unknown';
		return false;
	}
	
	function validate_field($name,$value,&$attribs)
	{
		// Verify it's the correct type/format
		switch ($attribs['type'])
		{
			case OAK::DATATYPE_INT:
				if (!is_numeric($value))
					return false;

				$attribs['converted_value']=(int)$value;
				break;
			case OAK::DATATYPE_TEXT:
				if (!is_string($value))
				{
					$attribs['validate_failure']='not string';
					return false;
				}

				$attribs['converted_value']=$value;

				// Check min/max length, if specified
				if (isset($attribs['minlen']) && isset($attribs['maxlen']) && !is_null($attribs['minlen']) && !is_null($attribs['maxlen']))
				{
					if ($attribs['minlen']<=strlen($attribs['converted_value']) && strlen($attribs['converted_value'])<=$attribs['maxlen'])
					{
						// OK
					}
					else
					{
						$attribs['validate_failure']='length outside minlen and maxlen';
						return false;
					}
				}
				break;
			case OAK::DATATYPE_MONEY:
				// TODO: support currency symbol
				if (!is_numeric($value))
					return false;

				$attribs['converted_value']=(float)$value;
				break;
			case OAK::DATATYPE_FLOAT:
				if (!is_numeric($value))
					return false;

				$attribs['converted_value']=(float)$value;
				break;
			case OAK::DATATYPE_BOOL:
				if (is_numeric($value))
					$attribs['converted_value']=$value?true:false;
				else if (is_string($value))
				{
					$value=trim($value);
					if (strcasecmp($value,'yes')==0)
						$attribs['converted_value']=true;
					else if (strcasecmp($value,'true')==0)
						$attribs['converted_value']=true;
					else if (strcasecmp($value,'no')==0)
						$attribs['converted_value']=false;
					else if (strcasecmp($value,'false')==0)
						$attribs['converted_value']=false;
					else
						$attribs['converted_value']=false;
				}
				else
					$attribs['converted_value']=$value?true:false;
				break;
			case OAK::DATATYPE_OBJ:
				// Shouldn't ever happen
				break;
			case OAK::DATATYPE_URI:
				if (!is_string($value))
					return false;
					
				if (!empty($value)) // URIs can be a zero-length string, if the app wants a minimum length, they can specify minlen in $cgi_fields
				{
					// Simple URI validation					
					$parts=parse_url($value);
					if (($parts['scheme']!='http') || // Must be HTTP
						!preg_match('/\./',$parts['host'])) // Must have at least one dot in it
						return false;
				}
					
				$attribs['converted_value']=$value;
				break;
			case OAK::DATATYPE_PHONE:
				if (empty($value) || preg_match('/[^A-Z0-9\(\)\s-]/',$value))
					return false;
					
				$attribs['converted_value']=(string)preg_replace('/\s+/','',$value); // remove multiple spaces
				break;
			default:
				throw new Exception("Unknown OAK datatype:".$attribs['type']);
		}
		
		if (self::validate_value_range(&$attribs)!==true)
			return false;
		
		// Call user func, if specified
		if (isset($attribs['validatefunc']) && is_callable($attribs['validatefunc']))
		{
			if (call_user_func($attribs['validatefunc'],$name,$value,$attribs,$attribs['converted_value'],$this)!==true)
				return false;
		}
		
		return true;
	}
	
	function load_cgi_fields(&$cgi_fields, $cgi_name_prefix='')
	{
		if (!is_array($cgi_fields))
			throw new Exception('$cgi_fields must be an array');
			
		foreach ($cgi_fields as $name=>&$attribs)
		{
			if ($attribs['type']==OAK::DATATYPE_OBJ)
			{
				$this->load_cgi_fields(&$attribs['properties'],$name.OAK::CGI_NAME_SEP);
			}
			else
			{
				$attribs['isset']=false;
				$attribs['validated']=false;

				$cgi_name=$cgi_name_prefix.$name;
			
				if (isset($_POST[$cgi_name]))
				{
					$attribs['isset']=true;
					$aattribs['value']=$_POST[$cgi_name];
					if ($this->validate_field($cgi_name,$_POST[$cgi_name],&$attribs))
						$attribs['validated']=true;
				}
				else if (isset($_GET[$cgi_name]))
				{
					$attribs['isset']=true;
					$attribs['value']=$_GET[$cgi_name];
					if ($this->validate_field($cgi_name,$_GET[$cgi_name],&$attribs))
						$attribs['validated']=true;
				}
			}
		}
	}
	
	function cgi_value_exists($name,$cgi_fields)
	{
		return $this->get_cgi_value($name,$cgi_fields)===null?false:true;
	}
	
	function get_cgi_value($name,$cgi_fields)
	{
		if (empty($name))
			throw new Exception('$name cannot be empty');

		$parts=split(OAK::CGI_NAME_SEP,$name);
		
		if ($cgi_fields[$parts[0]]['type']==OAK::DATATYPE_OBJ)
		{
			array_shift($parts); // Remove the first part
			return $this->get_cgi_value(implode(OAK::CGI_NAME_SEP,$parts),$cgi_fields[$parts[0]]['properties']);
		}

		if (isset($cgi_fields[$parts[0]]) && $cgi_fields[$parts[0]]['isset']===true && $cgi_fields[$parts[0]]['validated']===true)
			return $cgi_fields[$parts[0]]['converted_value'];
		return null;
	}
	
	function get_missing_field_count($cgi_fields)
	{
		$total=0;

		foreach ($cgi_fields as $name=>$attribs)
		{
			if ($cgi_fields['type']==OAK::DATATYPE_OBJ)
				$total+=$this->get_missing_field_count($cgi_fields['properties']);
			else if ($attribs['isset']==false && $attribs['flags']&OAK::FIELDFLAG_REQUIRED)
				++$total;
		}
		
		return $total;
	}
	
	function get_invalid_fields($cgi_fields,$cgi_name='')
	{
		$fields=array();

		foreach ($cgi_fields as $name=>$attribs)
		{
			if ($cgi_fields['type']==OAK::DATATYPE_OBJ)
			{
				$a=$this->get_invalid_fields($cgi_fields['properties'],$name.OAK::CGI_NAME_SEP);
				$fields=array_merge($fields,$a);
			}
			else if ($attribs['isset']===true && $attribs['validated']===false)
			{
				$fields[$cgi_name.$name]=$attribs;
			}
		}
		return $fields;
	}
	
	function assign_cgi_values($obj,$cgi_fields)
	{
		foreach ($cgi_fields as $name => $attribs) 
		{
			if ($attribs['type']==OAK::DATATYPE_OBJ)
			{
				$this->assign_cgi_values(&$obj->$name,$attribs['properties']);
			}
			else if (($attribs['flags']&OAK::FIELDFLAG_CGIONLY)==0 && $attribs['validated'])
			{
				$obj->$name=$attribs['converted_value'];
			}
		}
	}
	
	function get_document($id,$obj)
	{
		$couchdb=new CouchDB($this->config->couchdb->database);
		$rsp=$couchdb->send($id,"get");
		if ($rsp->getStatusCode()!=200)
		{
			return false; // No existing document
		}

		$doc=$rsp->getBody(true);
		foreach ($doc as $key => $value) {
			$obj->$key=$value;
		}
		
		return true;
	}
	
	function put_document($id,$doc)
	{
		if (!is_string($doc))
			$json=json_encode($doc);

		$couchdb=new CouchDB($this->config->couchdb->database);
		$rsp=$couchdb->send($id,"put",$json);

		if ($rsp->getStatusCode()==201)
		{
			$this->queue_doc_update($id);
			return true;
		}
			
		return false;
	}

	function put_document_json($id,$json)
	{
		$couchdb=new CouchDB($this->config->couchdb->database);
		$rsp=$couchdb->send($id,"put",$json);

		if ($rsp->getStatusCode()==201)
			return true;
			
		return false;
	}
	
	function copy_document($old_id,$new_id)
	{
		// NOTE: COPY only works in CouchDB 0.9+
		$couchdb=new CouchDB($this->config->couchdb->database);

		$rsp=$couchdb->send($old_id,"copy",$new_id);
		
		// var_dump($rsp->getHeaders());
		// var_dump($rsp->getBody(true));

		if ($rsp->getStatusCode()==201)
			return true;
			
		return false;
	}

	function delete_document($id)
	{
		$id=urlencode($id);
		$couchdb=new CouchDB($this->config->couchdb->database);
		
		$rsp=$couchdb->send($id,"get");
		if ($rsp->getStatusCode()!=200)
			return false; // No existing document

		$doc=$rsp->getBody(true);
		$del_id="$id?rev=".$doc->_rev;

		$rsp=$couchdb->send($del_id,"delete");
		
		if ($rsp->getStatusCode()==200)
			return true;
			
		return false;
	}

	function write_document($obj,$xmlwriter)
	{
		// Remove implementation-specific data
		$copyobj=$obj;
		unset($copyobj->_id);
		unset($copyobj->_rev);
		$this->json2xml($copyobj,$xmlwriter);
	}
	
	function json2xml($jsonobj,$xmlwriter,$tag=null)
	{
		if (is_bool($jsonobj))
		{
			$xmlwriter->text($jsonobj===true?'yes':'no');
		}
		else if (is_scalar($jsonobj))
		{
			$xmlwriter->text($jsonobj);
		}
		else if (is_array($jsonobj))
		{
			if (is_null($tag) || !is_string($tag))
				$tag="doc";

			$xmlwriter->startElement($tag);
			foreach ($jsonobj as $array_item)
			{
				$this->json2xml($array_item,$xmlwriter,'item');
			}
			$xmlwriter->endElement();
		}
		else if (is_object($jsonobj))
		{
			// Get the document element tag from the type property, if it exists
			if (property_exists($jsonobj,'type'))
			{
				if (is_null($tag) || !is_string($tag))
					$tag=$jsonobj->type;
			}
			else if (is_null($tag) || !is_string($tag))
				$tag="doc";

			$xmlwriter->startElement($tag);

			if (property_exists($jsonobj,'@attributes'))
			{
				$varname="@attributes"; // Need to do this because of the @
				foreach ($jsonobj->$varname as $k=>$v)
				{
					if (is_bool($v))
						$xmlwriter->writeAttribute($k,$v===true?'yes':'no');
					else
						$xmlwriter->writeAttribute($k,$v);
				}
			}
			
			foreach ($jsonobj as $k=>$v)
			{
				if ($k=="@attributes")
				{
					// Skip it, we did this above
				}
				else
				{
					if (is_bool($v))
						$xmlwriter->writeElement($k,$v===true?'yes':'no');
					else if (is_scalar($v))
						$xmlwriter->writeElement($k,$v);
					else if (is_object($v) || is_array($v))
					{
						$this->json2xml($v,$xmlwriter,$k);
					}
				}
			}

			$xmlwriter->endElement();
		}
		else
		{
			throw new Exception('Unknown datatype:'.gettype($jsonobj));
		}

	}
	
	function queue_doc_update($id)
	{
		$memQ=new Memcached();
		$memQ->addServers($this->config->memcacheq->servers);
		$memQ->set('updates',$id);
	}
	
	function get_queue_msg($queue_name)
	{
		$memQ=new Memcached();
		$memQ->addServers($this->config->memcacheq->servers);
		return $memQ->get($queue_name);
	}
	
	function put_queue_msg($queue_name,$msg)
	{
		$memQ=new Memcached();
		$memQ->addServers($this->config->memcacheq->servers);
		return $memQ->set($queue_name,$msg);
	}

	function config_bin_dir()
	{
		return $this->config->file_locations->BIN_DIR;
	}

	public function log_ident($ident)
	{
		$this->log_ident=$ident;
	}
	
	public function log($msg, $priority=OAK::LOGPRI_INFO)
	{
		syslog($priority,$this->log_ident.':'.$msg);
	}
	
};

?>