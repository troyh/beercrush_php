<?php
require_once('beercrush/couchdb.php');

class OAKDocument
{
	function __construct($type) 
	{
		$this->type=$type;
		if (!isset($this->meta))
			$this->meta=new stdClass;
		$this->meta->timestamp=time();
	}
	
	function __set($name,$val) 
	{
		switch ($name)
		{
		case "timestamp":
			$this->meta->$name;
			break;
		case "type":
		default:
			$this->$name=$val; 
			break;
			// throw new Exception('Unsupported property:'.$name);
			// break;
		}
	}
	function __get($name)   { return isset($this->$name)?$this->$name:null; }
	function __isset($name) { return isset($this->$name); }
	function __unset($name) { unset($this->$name); }
	
	function setID($id) 
	{
		if (empty($id))
			throw new Exception('empty id');
		$this->_id=$id;
		// $attribs="@attributes";
		// $this->$attribs->id=$id;
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
	const DATATYPE_ADDRESS_STATE=9;
	
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
		if (empty($conf_file))
			throw new Exception('Config file not found');
		// Read conf_file
		$conf_json=file_get_contents($conf_file);
		$this->config=json_decode($conf_json);
		if ($this->config==NULL)
			throw new Exception($conf_file.' could not be read');
		
		// Open syslog
		openlog('OAK',LOG_ODELAY|LOG_CONS|LOG_PID,LOG_LOCAL0);
		if (!empty($_SERVER['SCRIPT_NAME']))
			$this->log_ident($_SERVER['SCRIPT_NAME']);
		else
			$this->log_ident(__FILE__);
	}
	
	function __destruct() 
	{
		// Close syslog
		closelog();
	}
	
	/*
	 * User Credentials Functions
	 */
	public function create_uuid()
	{
		uuid_create(&$uuid);
		uuid_make($uuid,UUID_MAKE_V1);
		uuid_export($uuid,UUID_FMT_STR,&$uuid_string);
		$uuid_string=trim($uuid_string); // Remove the trailing null-byte (why is it added?!)
		return $uuid_string;
	}

	function get_user_id()
	{
		if (!empty($_GET['userid']))
			return $_GET['userid'];
		if (!empty($_POST['userid']))
			return $_POST['userid'];
		return null;
	}

	function get_user_key()
	{
		if (!empty($_GET['usrkey']))
			return $_GET['usrkey'];
		if (!empty($_POST['usrkey']))
			return $_POST['usrkey'];
		return null;
	}
	
	function is_debug_on()
	{
		return isset($this->config->debug) && $this->config->debug==="yes";
	}
	
	public function get_file_location($value)
	{
		if (!isset($this->config->file_locations->$value))
			throw new Exception('file location '.$value.' does not exist');
		return $this->config->file_locations->$value;
	}
	
	public function request_login()
	{
		// NOTE: a 401 return status screws up the iPhone's NSURLConnection class
		header("HTTP/1.0 403 Login required");
		// header("Content-Type: text/plain");
		print "Login required\n";
	}

	function login_is_trusted()
	{
		// TODO: use memcached or something quick so that we don't have to use couchdb on *every* authenticated request!
		$user_doc=new OAKDocument('');
		if ($this->get_document('user:'.$this->get_user_id(),$user_doc)!==true)
			return false;
		
		$correct_key=md5($this->get_user_id().$user_doc->secret.$_SERVER['REMOTE_ADDR']);
		if ($correct_key!==$this->get_user_key())
			return false;
			
		return true;
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
					// Lowercase it
					$value=strtolower($value);
					
					// Simple URI validation					
					$parts=parse_url($value);
					if (($parts['scheme']!='http') || // Must be HTTP
						!preg_match('/\./',$parts['host'])) // Must have at least one dot in it
						return false;
				}
					
				$attribs['converted_value']=$value;
				break;
			case OAK::DATATYPE_PHONE:
				if (empty($value))
				{
					if ($attribs['minlen']>0)
						return false;
				}
				else if (preg_match('/[^A-Z0-9\(\)\s-]/',$value))
					return false;
					
				$attribs['converted_value']=(string)preg_replace('/\s+/','',$value); // remove multiple spaces
				break;
			case OAK::DATATYPE_ADDRESS_STATE:
			{
				// From http://www.usps.com/ncsc/lookups/usps_abbreviations.html
				$state_abbreviations=array(
					"ALABAMA"							=> 'AL',
					"ALASKA"							=> 'AK',
					"AMERICAN SAMOA"					=> 'AS',
					"ARIZONA"							=> 'AZ',
					"ARKANSAS"							=> 'AR',
					"CALIFORNIA"						=> 'CA',
					"COLORADO"							=> 'CO',
					"CONNECTICUT"						=> 'CT',
					"DELAWARE"							=> 'DE',
					"DISTRICT OF COLUMBIA"				=> 'DC',
					"FEDERATED STATES OF MICRONESIA"	=> 'FM',
					"FLORIDA"							=> 'FL',
					"GEORGIA"							=> 'GA',
					"GUAM"								=> 'GU',
					"HAWAII"							=> 'HI',
					"IDAHO"								=> 'ID',
					"ILLINOIS"							=> 'IL',
					"INDIANA"							=> 'IN',
					"IOWA"								=> 'IA',
					"KANSAS"							=> 'KS',
					"KENTUCKY"							=> 'KY',
					"LOUISIANA"							=> 'LA',
					"MAINE"								=> 'ME',
					"MARSHALL ISLANDS"					=> 'MH',
					"MARYLAND"							=> 'MD',
					"MASSACHUSETTS"						=> 'MA',
					"MICHIGAN"							=> 'MI',
					"MINNESOTA"							=> 'MN',
					"MISSISSIPPI"						=> 'MS',
					"MISSOURI"							=> 'MO',
					"MONTANA"							=> 'MT',
					"NEBRASKA"							=> 'NE',
					"NEVADA"							=> 'NV',
					"NEW HAMPSHIRE"						=> 'NH',
					"NEW JERSEY"						=> 'NJ',
					"NEW MEXICO"						=> 'NM',
					"NEW YORK"							=> 'NY',
					"NORTH CAROLINA"					=> 'NC',
					"NORTH DAKOTA"						=> 'ND',
					"NORTHERN MARIANA ISLANDS"			=> 'MP',
					"OHIO"								=> 'OH',
					"OKLAHOMA"							=> 'OK',
					"OREGON"							=> 'OR',
					"PALAU"								=> 'PW',
					"PENNSYLVANIA"						=> 'PA',
					"PUERTO RICO"						=> 'PR',
					"RHODE ISLAND"						=> 'RI',
					"SOUTH CAROLINA"					=> 'SC',
					"SOUTH DAKOTA"						=> 'SD',
					"TENNESSEE"							=> 'TN',
					"TEXAS"								=> 'TX',
					"UTAH"								=> 'UT',
					"VERMONT"							=> 'VT',
					"VIRGIN ISLANDS"					=> 'VI',
					"VIRGINIA"							=> 'VA',
					"WASHINGTON"						=> 'WA',
					"WEST VIRGINIA"						=> 'WV',
					"WISCONSIN"							=> 'WI',
					"WYOMING"							=> 'WY',
				);                                              
				
				$state_codes=array(
					'AL','AK','AS','AZ','AR','CA','CO','CT','DE','DC','FM','FL','GA',
					'GU','HI','ID','IL','IN','IA','KS','KY','LA','ME','MH','MD','MA',
					'MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND',
					'MP','OH','OK','OR','PW','PA','PR','RI','SC','SD','TN','TX','UT',
					'VT','VI','VA','WA','WV','WI','WY'
				);

				$value=strtoupper($value);
				if (in_array($value,$state_codes))
					$attribs['converted_value']=$value;
				else if (isset($state_abbreviations[$value]))
					$attribs['converted_value']=(string)$state_abbreviations[$value];
				else
					return false;
					
				break;
			}
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
	
	function set_cgi_value($name,&$cgi_fields,$value)
	{
		if (empty($name))
			throw new Exception('$name cannot be empty');

		$parts=split(OAK::CGI_NAME_SEP,$name);
		
		if ($cgi_fields[$parts[0]]['type']==OAK::DATATYPE_OBJ)
		{
			$k=$parts[0];
			array_shift($parts); // Remove the first part
			$this->set_cgi_value(implode(OAK::CGI_NAME_SEP,$parts),$cgi_fields[$k]['properties'],$value);
		}
		else if (isset($cgi_fields[$parts[0]]))
		{
			$cgi_fields[$parts[0]]['isset']=true;
			$cgi_fields[$parts[0]]['validated']=true;
			$cgi_fields[$parts[0]]['converted_value']=$value;
		}
	}
	
	function get_cgi_value($name,$cgi_fields)
	{
		if (empty($name))
			throw new Exception('$name cannot be empty');

		$parts=split(OAK::CGI_NAME_SEP,$name);
		
		if ($cgi_fields[$parts[0]]['type']==OAK::DATATYPE_OBJ)
		{
			$k=$parts[0];
			array_shift($parts); // Remove the first part
			return $this->get_cgi_value(implode(OAK::CGI_NAME_SEP,$parts),$cgi_fields[$k]['properties']);
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
			if ($attribs['type']==OAK::DATATYPE_OBJ)
				$total+=$this->get_missing_field_count($attribs['properties']);
			else if ($attribs['isset']==false && (isset($attribs['flags']) && $attribs['flags']&OAK::FIELDFLAG_REQUIRED))
				++$total;
		}
		
		return $total;
	}
	
	function get_invalid_fields($cgi_fields,$cgi_name='')
	{
		$fields=array();

		foreach ($cgi_fields as $name=>$attribs)
		{
			if ($attribs['type']==OAK::DATATYPE_OBJ)
			{
				$a=$this->get_invalid_fields($attribs['properties'],$name.OAK::CGI_NAME_SEP);
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
				if (is_null($obj->$name))
					$obj->$name=new stdClass;
				$this->assign_cgi_values(&$obj->$name,$attribs['properties']);
			}
			else if ((!isset($attribs['flags']) || ($attribs['flags']&OAK::FIELDFLAG_CGIONLY)==0) && $attribs['validated'])
			{
				$obj->$name=$attribs['converted_value'];
			}
		}
	}
	
	function get_document($id,$obj,$rev=null)
	{
		if (empty($id))
			throw new Exception('id is empty');
		$couchdb=new CouchDB($this->config->couchdb->database);

		if (!is_null($rev))
			$id=$id.'?rev='.$rev;

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
	
	public function get_view($url,$obj)
	{
		list($designname,$viewname)=preg_split("/\//",$url,2);
		return $this->get_document('_design/'.$designname.'/_view/'.$viewname,$obj);
	}
	
	function put_document($id,$doc)
	{
		if (is_string($doc))
			$json=$doc;
		else
		{
			if (is_object($doc))
			{
				if (!isset($doc->meta))
					$doc->meta=new StdClass;
				$doc->meta->mtime=time(); // Record modified time
			}
			$json=json_encode($doc);
		}

		$couchdb=new CouchDB($this->config->couchdb->database);
		$rsp=$couchdb->send($id,"put",$json);

		if ($rsp->getStatusCode()==201)
		{
			$body=$rsp->getBody(true);

			// Record pertinent info about the change and put it in the updates queue
			$update_msg=array(
				'docid'=>$body->id,
				'old_rev'=>$doc->_rev,
				'new_rev'=>$body->rev,
				'timestamp'=>time()
			);
			if ($this->get_user_id()!=null)
				$update_msg['user_id']=$this->get_user_id();
			$this->put_queue_msg('updates',json_encode($update_msg));
			return true;
		}
			
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
	
	function write_document_json($obj)
	{
		$copyobj=$obj;
		unset($copyobj->_id);
		unset($copyobj->_rev);
		print json_encode($copyobj);
	}
	
	public function write_document_to_xmlfile($doc,$filename)
	{
		if (is_string($doc)) // Assume it's an ID string
		{
			$id=$doc;
			$doc=new OAKDocument('');
			if ($this->get_document($id,&$doc)===false)
				throw new Exception("Unable to get document $id");
		}
		else if (is_object($doc) && (get_class($doc)==='OAKDocument' || is_subclass_of($doc,'OAKDocument')))
		{
		}
		else
			throw new Exception('Unsupported argument');

		$xmlwriter=new XMLWriter;
		$xmlwriter->openMemory();
		$xmlwriter->startDocument();

		$this->write_document($doc,$xmlwriter);

		$xmlwriter->endDocument();

		if (!is_dir(dirname($filename)))
		{
			if (mkdir(dirname($filename),0777,true)===false)
				$this->log('Unable to mkdir '.dirname($filename));
		}

		file_put_contents($filename,$xmlwriter->outputMemory());
		$this->log('Wrote '.$filename);
	}
	
	public function write_document_to_jsonfile($doc,$filename)
	{
		if (is_string($doc)) // Assume it's an ID string
		{
			$id=$doc;
			$doc=new OAKDocument('');
			if ($this->get_document($id,&$doc)===false)
				throw new Exception("Unable to get document $id");
		}
		else if (is_object($doc) && (get_class($doc)==='OAKDocument' || is_subclass_of($doc,'OAKDocument')))
		{
		}
		else
			throw new Exception('Unsupported argument');

		// Make sure directory is there for the files we will create/update
		if (!is_dir(dirname($filename)))
		{
			if (mkdir(dirname($filename),0777,true)===false)
				$this->log('Unable to mkdir '.dirname($filename));
		}

		file_put_contents($filename,json_encode($doc));
		$this->log('Wrote '.$filename);
	}
	
	public function persist_document($doc,$alternate_path=null)
	{
		if (!isset($this->config->doc_persistence->locations))
			throw new Exception('Document persistence locations unknown');

		if (is_string($doc)) // Assume it's an ID string
			$parts=split(':',$doc);
		else if (is_object($doc) && (get_class($doc)==='OAKDocument' || is_subclass_of($doc,'OAKDocument')))
			$parts=split(':',$doc->getID());
		else
			throw new Exception('Unsupported argument');
			
		if (!empty($this->config->doc_persistence->locations->xml))
		{
			if (is_null($alternate_path))
				$fullpath=$this->config->doc_persistence->locations->xml.'/'.join('/',$parts).'.xml';
			else
				$fullpath=$this->config->doc_persistence->locations->xml.'/'.$alternate_path.'.xml';
			$this->write_document_to_xmlfile($doc,$fullpath);
		}

		if (!empty($this->config->doc_persistence->locations->json))
		{
			if (is_null($alternate_path))
				$fullpath=$this->config->doc_persistence->locations->json.'/'.join('/',$parts).'.json';
			else
				$fullpath=$this->config->doc_persistence->locations->json.'/'.$alternate_path.'.json';
			$this->write_document_to_jsonfile($doc,$fullpath);
		}
	}
	
	function json2xml($jsonobj,$xmlwriter,$tag=null)
	{
		if (is_bool($jsonobj))
		{
			if (!is_null($tag))
				$xmlwriter->writeElement($tag,($jsonobj===true?'yes':'no'));
			else
				$xmlwriter->text($jsonobj===true?'yes':'no');
		}
		else if (is_scalar($jsonobj))
		{
			if (!is_null($tag))
				$xmlwriter->writeElement($tag,$jsonobj);
			else
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
	
	function jsontidy($json,$level=0)
	{
		if (is_object($json))
		{
			$props=array();
			foreach ($json as $k=>$v)
			{
				$props[]="\"$k\": ".$this->jsontidy($v,$level+1);
			}
			$indent=str_repeat("\t",$level+1);
			return "{\n$indent".join(",\n$indent",$props)."\n".str_repeat("\t",$level)."}";
		}
		else if (is_array($json))
		{
			$props=array();
			foreach ($json as $a)
			{
				$props[]=$this->jsontidy($a,$level+1);
			}
			$indent=str_repeat("\t",$level+1);
			return "[\n$indent".join(",\n$indent",$props)."\n".str_repeat("\t",$level)."]";
		}
		else if (is_string($json))
			return json_encode($json); // json_encode handles JSON-special characters easily
		else if (is_numeric($json))
			return $json;
		else if (is_bool($json))
			return $json?"true":"false";
		else if (is_null($json))
			return "null";
		return "";
	}
	
	function get_queue_msg($queue_name)
	{
		$memQ=new Memcached();
		$memQ->addServers($this->config->memcacheq->servers);
		$msg=$memQ->get($queue_name);
		if ($msg===FALSE)
			return FALSE;
		return json_decode($msg);
	}
	
	function put_queue_msg($queue_name,$msg)
	{
		if (!is_string($msg))
			$msg=json_encode($msg);
			
		$memQ=new Memcached();
		$memQ->addServers($this->config->memcacheq->servers);
		return $memQ->set($queue_name,$msg);
	}

	public function log_ident($ident)
	{
		$this->log_ident=$ident;
	}
	
	public function log($msg, $priority=OAK::LOGPRI_INFO)
	{
		syslog($priority,$this->log_ident.':'.$msg);
	}
	
	public function get_config_info()
	{
		return $this->config;
	}
	
	function get_database_name()
	{
		return $this->config->couchdb->database;
	}
	
	public function make_image_size($original,$size)
	{
		if (!isset($this->config->photos->sizes->$size))
			throw new Exception('Unknown photo size:'.$size);
		if (!isset($this->config->photos->sizes->$size->maxdim))
			throw new Exception('Misconfigured photo size:'.$size);
			
		$newimage = new Imagick($original);

		if ($this->config->photos->sizes->$size->thumbnail)
		{	// Make a thumbnail, a special-case image size
			$new_width=$this->config->photos->sizes->$size->maxdim;
			$new_height=$this->config->photos->sizes->$size->maxdim;

			$newimage->cropThumbnailImage($new_width,$new_height);
		}
		else
		{	// Calculate the dimensions
			$original_width=$newimage->getImageWidth();
			$original_height=$newimage->getImageHeight();

			if ($original_width >= $original_height) // Landscape
			{
				$new_width=$this->config->photos->sizes->$size->maxdim;
				$new_height=(int)($original_height*($new_width/$original_width));
			}
			else // Portrait
			{
				$new_height=$this->config->photos->sizes->$size->maxdim;
				$new_width=(int)($original_width*($new_height/$original_height));
			}
			$newimage->resizeImage($new_width,$new_height,Imagick::FILTER_LANCZOS,1);
		}
		
		// Calculate the filename
		$pi=pathinfo($original);
		$newimage_filename=$pi['dirname'].'/'.$size.'.'.$pi['extension'];
		
		$newimage->writeImage($newimage_filename);
		$newimage->destroy(); 

		// Return info about new photo
		return array(
			'filename' => $newimage_filename,
			'size' => array('width' => $new_width, 'height' => $new_height)
		);
	}
	
	public function broadcast_msg($type,$msg)
	{
		// TODO: broadcast the message via the Spread Toolkit (or something similar)
	}
	
	public function query($query_string,$return_json=TRUE,$doctypes=null)
	{
		$fq_param='';
		if (!is_null($doctypes))
		{
			if (!is_array($doctypes))
				$doctypes=array($doctypes);
				
			$fq_param='fq='.urlencode('doctype:'.join(' or doctype:',$doctypes)).'&';
		}
		// Pick a node
		$node=$this->config->solr->nodes[rand()%count($this->config->solr->nodes)];
		$url='http://'.$node.$this->config->solr->url.'/select/?'.$fq_param.'wt=json&rows=20&qt=dismax&mm=1&q='.urlencode($query_string);
		$results=@file_get_contents($url);
		if ($return_json)
			return json_decode($results);
		return $results;
	}

	public function solr_post($url,$xmldoc)
	{
		$ch=curl_init('http://'.$this->config->solr->nodes[0].$this->config->solr->url.$url);
		if ($ch===FALSE)
			throw new Exception('curl_init failed');

		curl_setopt($ch,CURLOPT_HTTPHEADER,array(
			'Content-Type: text/xml; charset=utf-8'
		));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xmldoc);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		// curl_setopt($ch,CURLOPT_VERBOSE,TRUE);

		$output=curl_exec($ch);
		$status_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
		return $status_code;
	}

	public function remove_diacritics($string) {
	    $table = array(
	        'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
	        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
	        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
	        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'ü' => 'u', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
	        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
	        'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
	        'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
	        'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', 'Ś'=>'S', 'Ą'=>'A'
	    );

	    return strtr($string, $table);
	}

	
};

?>