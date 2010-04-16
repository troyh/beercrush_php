<?php

class OAKSolrIndexer {

	private $oak=null;
	private $schema=null;
	private $batch_doc_count=0;
	
	function __construct(OAK $oak,stdClass $schema) {
		$this->oak=$oak;
		$this->schema=$schema;
	}
	
	function index_doc($doc) {
		$xmlwriter=$this->batch_index_doc_start();
		$this->write_xml_doc($doc,$xmlwriter);
		$this->batch_index_doc_end($xmlwriter);
		$this->oak->log("Indexed doc: ".$doc->{$this->schema->doc_id});
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
		$this->write_xml_doc($doc,$xmlwriter);
		$this->batch_doc_count++;
	}
	
	public function batch_index_doc_end($xmlwriter) {
		$xmlwriter->endElement(); // </add>
		$xmlwriter->endDocument();
		
		$xmldoc=$xmlwriter->outputMemory();
		// print $xmldoc;

		// Submit to Solr
		$status_code=$this->solr_post('/update',$xmldoc);
		if ($status_code!=200)
			$this->oak->log("Solr index update failed: $status_code",OAK::LOGPRI_ERR);
		else
		{
			$status_code=$this->solr_post('/update','<commit/>');
			if ($status_code!=200)
				$this->oak->log("Solr index commit failed: $status_code",OAK::LOGPRI_ERR);
			else {
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
			if (is_object($info)) {
				$propname=$info->propname;
				$datatype=$info->fieldtype;
			}
			else if (is_string($info)) {
				$propname=$field;
				$datatype=$info;
			}
			else {
				throw new Exception('Unhandled info type: '.gettype($info));
			}
		
			switch ($datatype) {
				case "text":
					$v=$this->get_property_value($doc,$propname);
					$v=trim($v);
					if (!empty($v))
						$this->writeValue($field,$v,'text',$xmlwriter);
					break;
				case "integer":
					$this->writeValue($field,$this->get_property_value($doc,$propname),'integer',$xmlwriter);
					break;
				default:
					throw new Exception('Unknown datatype: $datatype');
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
