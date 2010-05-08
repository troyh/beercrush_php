<?php
require_once('OAK/oak.class.php');

class BeerCrush
{
	// Useful Defines
	const DATE_FORMAT='D, d M Y H:i:s O';
	const DEFAULT_AVATAR_URL="/img/default_avatar.gif";
	const CONF_FILE='/etc/BeerCrush/webapp.conf';
	const SETUP_CONF='/etc/BeerCrush/setup.conf';

	static $api_doc_cache;
	
	static function api_doc($oak,$url)
	{
		$url=urlencode($url);
		if (!isset(BeerCrush::$api_doc_cache[$url])) {
			BeerCrush::$api_doc_cache[$url]=json_decode(@file_get_contents($oak->get_config_info()->api->base_uri.'/'.ltrim($url,'/')));
		}
		return BeerCrush::$api_doc_cache[$url];
	}
	
	static function docid_to_docurl($docid) {
		return str_replace(':','/',$docid);
	}
	
	static function get_review_type($review_id) {
		$parts=explode(':',$review_id);
		return $parts[1];
	}
	
	static function beer_id_to_brewery_id($id) {
		$parts=explode(':',$id);
		return 'brewery:'.$parts[1];
	}

	static public function brewery_name_from_beerdoc($oak,$doc) {
		$brewerydoc=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($doc->brewery_id));
		return trim($brewerydoc->name);
	}

	public $oak;
	
	public function __construct() {
		$this->api_doc_cache=array();
		$this->oak=new OAK(BeerCrush::CONF_FILE);
	}
	
	public function docobj($docid) {
		return BeerCrush::api_doc($this->oak,BeerCrush::docid_to_docurl($docid));
	}
	
};

$BC=new BeerCrush();

?>