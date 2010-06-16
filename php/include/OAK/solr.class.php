<?php

class OAKSolrIndexer {

	private $oak=null;
	private $schema=null;
	private $batch_doc_count=0;
	
	function __construct(OAK $oak,stdClass $schema) {
		$this->oak=$oak;
		$this->schema=$schema;
		
		// Include any PHP includes
		if (!empty($this->schema->phpincludes)) {
			if (!is_array($this->schema->phpincludes))
				$this->schema->phpincludes=array($this->schema->phpincludes);
				
			foreach ($this->schema->phpincludes as $inc) {
				include_once($inc);
			}
		}
	}
	
	function index_doc($doc) {
		$xmlwriter=$this->batch_index_doc_start();
		if ($this->batch_index_doc($doc,$xmlwriter)) {
			// $this->oak->log("Indexed doc: ".$doc->{$this->schema->doc_id});
			$this->batch_index_doc_end($xmlwriter);
			return true;
		}
		return false;
	}
	
	public function batch_index_doc_start() {
		$xmlwriter=new XMLWriter;
		$xmlwriter->openMemory();
		$xmlwriter->startDocument();
		$xmlwriter->startElement('add');
		
		$this->batch_doc_count=0;
		
		return $xmlwriter;
	}
	
	public function batch_index_doc($doc,$xmlwriter) {
		// Only index the document if it's in the schema and there's an ID field to use
		if (!empty($this->schema->doctypes->{$doc->type}) && !empty($doc->{$this->schema->doc_id})) {
			$this->write_xml_doc($doc,$xmlwriter);
			$this->batch_doc_count++;
			return true;
		}
		return false;
	}
	
	public function batch_index_doc_end($xmlwriter) {
		$xmlwriter->endElement(); // </add>
		$xmlwriter->endDocument();
		
		$xmldoc=$xmlwriter->outputMemory();
		// print $xmldoc;
		// $this->oak->log($xmldoc);

		// Submit to Solr
		$status_code=$this->solr_post('/update',$xmldoc);
		if ($status_code!=200)
			$this->oak->log("Solr index update failed: $status_code",OAK::LOGPRI_ERR);
		else
		{
			$status_code=$this->solr_post('/update','<commit/>');
			if ($status_code!=200)
				$this->oak->log("Solr index commit failed: $status_code",OAK::LOGPRI_ERR);
			else if ($this->batch_doc_count > 1) {
				$this->oak->log("Batch indexed {$this->batch_doc_count} docs");
			}
		}
	}
	
	public function optimize() {
		$status_code=$this->solr_post('/update','<optimize/>');
		if ($status_code!=200)
			$this->oak->log("Solr index optimize failed: $status_code",OAK::LOGPRI_ERR);
		else {
			$this->oak->log('Optimized Solr index');
		}
	}
	
	private function write_xml_doc($doc,$xmlwriter) {
		$xmlwriter->startElement('doc');

		// Write id
		$this->writeValue('id',$doc->{$this->schema->doc_id},'text',$xmlwriter);

		// Write all other fields in the schema
		foreach ($this->schema->doctypes->{$doc->type} as $field=>$info) {
			$propname=null;
			$funcname=null;
			if (is_object($info)) {
				if (!empty($info->propname)) {
					$propname=$info->propname;
				}
				else if (!empty($info->php_function)) {
					$funcname=$info->php_function;
				}
				$datatype=$info->fieldtype;
			}
			else if (is_string($info)) {
				$propname=$field;
				$datatype=$info;
			}
			else {
				$this->oak->log('Unhandled info type: '.gettype($info));
				throw new Exception('Unhandled info type: '.gettype($info));
			}
		
			switch ($datatype) {
				case "text":
					if (!is_null($propname))
						$v=$this->get_property_value($doc,$propname);
					else if (!is_null($funcname))
						$v=$this->get_property_value_from_function($doc,$funcname);
					else
						throw new Exception('No property name or function specified in schema for '.$field);
					$v=trim($v);
					if (!empty($v))
						$this->writeValue($field,$v,'text',$xmlwriter);
					break;
				case "integer":
					if (!is_null($propname))
						$v=$this->get_property_value($doc,$propname);
					else if (!is_null($funcname))
						$v=$this->get_property_value_from_function($doc,$funcname);
					else
						throw new Exception('No property name or function specified in schema for '.$field);
					if (is_numeric($v))
						$this->writeValue($field,$v,'integer',$xmlwriter);
					break;
				case "text_array":
					if (isset($doc->$propname)) {
						foreach ($doc->$propname as $v) {
							$this->writeValue($field,$v,'text',$xmlwriter);
						}
					}
					break;
				case 'date':
					// Format: 1995-12-31T23:59:59Z
					if (!is_null($propname)) {
						$v=$this->get_property_value($doc,$propname);
						if (!is_numeric($v)) // Assumes Unix timestamp if it's numeric
							$v=strtotime($v);

						if ($v!==false) {
							$v=gmdate('c',$v);
							$this->writeValue($field,substr($v,0,19).'Z','tdate',$xmlwriter);
						}
					}
					break;
				case 'float':
					if (!is_null($propname))
						$v=$this->get_property_value($doc,$propname);
					else if (!is_null($funcname))
						$v=$this->get_property_value_from_function($doc,$funcname);
					else
						throw new Exception('No property name or function specified in schema for '.$field);
					if (is_numeric($v))
						$this->writeValue($field,(float)$v,'tfloat',$xmlwriter);
					break;
				default:
					$this->oak->log('Unknown datatype:'.$datatype);
					throw new Exception('Unknown datatype:'.$datatype);
					break;
			}
		}
	
		$xmlwriter->endElement(); // </doc>
		
	}

	private function writeValue($k,$v,$type,$xmlwriter)
	{
		$xmlwriter->startElement('field');
	
		$xmlwriter->writeAttribute('name',$k);
		$xmlwriter->writeAttribute('type',$type);
		$xmlwriter->text($v);
	
		$xmlwriter->endElement();
	}

	private function get_property_value($doc,$propdescriptor) {
		$parts=explode('.',$propdescriptor);
		$ref=$doc;
		foreach ($parts as $part) {
			$ref=$ref->$part;
		}
		return $ref;
	}
	
	private function get_property_value_from_function($doc,$function_name) {
		return call_user_func($function_name,$this->oak,$doc);
	}
	
	private function solr_post($url,$xmldoc)
	{
		$cfg=$this->oak->get_config_info();
		$ch=curl_init('http://'.$cfg->solr->master_node.$cfg->solr->url.$url);
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

	
}

?>
