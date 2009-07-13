<?php
require_once('beercrush/couchdb.php');

class OAK
{
	private $config;

	function __construct($conf_file) 
	{
		// global $conf_file;
		// Read conf_file
		$conf_json=file_get_contents($conf_file);
		$this->config=json_decode($conf_json);
	}
	
	function __destruct() {}
	
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
	
	function validate_field($name,$value,$attribs)
	{
		$validated=true;
		
		// Verify it's the correct type/format
		switch ($attribs['type'])
		{
			case OAK_DATATYPE_INT:
				if (is_numeric($value))
					$attribs['converted_value']=(int)$value;
				else
					$validated=false;
				break;
			case OAK_DATATYPE_TEXT:
				if (is_string($value))
					$attribs['converted_value']=$value;
				else
					$validated=false;
				break;
			case OAK_DATATYPE_MONEY:
				if (is_numeric($value) && is_float($value))
					$attribs['converted_value']=(float)$value;
				else
					$validated=false;
				break;
			case OAK_DATATYPE_BOOL:
				if ($value)
					$attribs['converted_value']=true;
				else
					$attribs['converted_value']=false;
				break;
			default:
				throw new Exception("Unknown OAK datatype:".$attribs['type']);
				
		}
		
		if ($validated===true)
		{
			// Verify it's within the range, if specified
			if ($attribs['min']<=$attribs['max'])
			{
				if ($attribs['min']<=$attribs['converted_value'] && $attribs['converted_value']<=$attribs['max'])
				{
					// Do nothing
				}
				else
				{
					$validated=false;
				}
			}
		}
		
		if ($validated===true)
		{
			// Call user func, if specified
			if ($attribs['userfunc'] && is_callable($attribs['userfunc']))
			{
				if ($attribs['userfunc']($name,$value,$attribs,$attribs['converted_value'])!==true)
					$validated=false;
			}
		}
		
		return $validated;
	}
	
	function load_cgi_fields()
	{
		global $cgi_fields;
		foreach ($cgi_fields as $name=>&$attribs)
		{
			$cgi_fields[$name]['isset']=false;
			$cgi_fields[$name]['validated']=false;
			
			if (isset($_POST[$name]))
			{
				$cgi_fields[$name]['isset']=true;
				$cgi_fields[$name]['value']=$_POST[$name];
				if ($this->validate_field($name,$_POST[$name],&$attribs))
					$cgi_fields[$name]['validated']=true;
			}
			else if (isset($_GET[$name]))
			{
				$cgi_fields[$name]['isset']=true;
				$cgi_fields[$name]['value']=$_GET[$name];
				if ($this->validate_field($name,$_GET[$name],&$attribs))
					$cgi_fields[$name]['validated']=true;
			}
		}
	}
	
	function get_cgi_value($name)
	{
		global $cgi_fields;
		if (!isset($cgi_fields[$name]) || $cgi_fields[$name]['isset']!==true || $cgi_fields[$name]['validated']!==true)
			throw new Exception("CGI field $name not available");
		return $cgi_fields[$name]['converted_value'];
	}
	
	function get_missing_field_count()
	{
		$total=0;

		global $cgi_fields;
		foreach ($cgi_fields as $name=>$attribs)
		{
			if ($attribs['isset']==false && $attribs['flags']&OAK_FIELDFLAG_REQUIRED)
				++$total;
		}
		return $total;
	}
	
	function get_invalid_field_count()
	{
		$total=0;

		global $cgi_fields;
		foreach ($cgi_fields as $name=>$attribs)
		{
			if ($attribs['isset']==true && $attribs['validated']==false)
				++$total;
		}
		return $total;
	}
	
	function __get($name) {
		global $cgi_fields;
		return $cgi_fields[$name]['converted_value'];
	}
	
	function assign_values($obj)
	{
		global $cgi_fields;
		foreach ($cgi_fields as $name => $attribs) {
			if ($attribs['validated'])
			{
				$obj->$name=$cgi_fields[$name]['converted_value'];
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
		$couchdb=new CouchDB($this->config->couchdb->database);

		$rsp=$couchdb->send($id,"delete");
		
		if ($rsp->getStatusCode()==200)
			return true;
			
		return false;
	}

	function json2xml($jsonobj,$xmlwriter,$tag=null)
	{
		if (is_scalar($jsonobj))
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
					if (is_scalar($v))
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
	
	function log($msg)
	{
		print "$msg\n"; // TODO: write to a logfile
	}
	
};

?>