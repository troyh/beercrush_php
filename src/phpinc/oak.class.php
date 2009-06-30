<?php
require_once('beercrush/couchdb.php');

class OAK
{
	private $config;

	function __construct() 
	{
		global $conf_file;
		// Read conf_file
		$conf_json=file_get_contents($conf_file);
		$this->config=json_decode($conf_json);
	}
	
	function __destruct() {}
	
	function get_user_id()
	{
		return "troyh";
	}
	
	function get_database_name()
	{
		return $this->config->couchdb->database;
	}
	
	
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
		// print "Storing doc:$id:".$json."<br />";
		// print "Database name:".$this->config->couchdb->database."<br />";
		$couchdb=new CouchDB($this->config->couchdb->database);
		$rsp=$couchdb->send($id,"put",$json);

		var_dump($rsp->getHeaders());
		var_dump($rsp->getBody(true));
			
		if ($rsp->getStatusCode()==201)
			return true;
			
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
	
};

?>