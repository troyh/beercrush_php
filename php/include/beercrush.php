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
		$url=$oak->get_config_info()->api->base_uri.'/'.ltrim($url,'/');
		if (!isset(BeerCrush::$api_doc_cache[$url])) {
			BeerCrush::$api_doc_cache[$url]=json_decode($oak->get_http_document($url));
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
	
	static public function is_a_menu($id) {
		return substr($id,0,5)==='menu:';
	}

	public $oak;
	
	public function __construct() {
		$this->api_doc_cache=array();
		$this->oak=new OAK(BeerCrush::CONF_FILE);
	}
	
	public function docobj($docid) {
		return BeerCrush::api_doc($this->oak,BeerCrush::docid_to_docurl($docid));
	}

	static public function beer_html_mini($beer) {
		global $BC;
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::beer_id_to_brewery_id($beer->id));
		$styles=BeerCrush::api_doc($BC->oak,'style/flatlist');
?>
	<a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a>
	<a href="/<?=BeerCrush::docid_to_docurl($beer->id)?>"><?=$beer->name?></a>

	<?php if ($beer->photos->total && $beer->photos->thumbnail):?><div><img src="<?=$beer->photos->thumbnail?>" /></div><?php endif;?>
	<div class="star_rating" title="Rating: <?=$beer->review_summary->avg?> of 5"><div class="avgrating" style="width: <?=$beer->review_summary->avg/5*100?>%"></div></div>
	<?php if (!empty($beer->styles[0])):?><div>Style:<?=$styles->{$beer->styles[0]}->name?></div><?php endif;?>
<?php
	}

	static public function beer_html_full($beer) {
		global $BC;
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::beer_id_to_brewery_id($beer->id));
?>
	<a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a>
	<a href="/<?=BeerCrush::docid_to_docurl($beer->id)?>"><?=$beer->name?></a>
	
	<?php if ($beer->photos->total && $beer->photos->thumbnail):?><div><img src="<?=$beer->photos->thumbnail?>" /></div><?php endif;?>
	<div class="star_rating" title="Rating: <?=$beer->review_summary->avg?> of 5"><div class="avgrating" style="width: <?=$beer->review_summary->avg/5*100?>%"></div></div>
	<div>Description:<?=$beer->description?></div>
	<div>Flavors:<?=join(', ',$flavor_names)?></div>
	<div>Body:<?=$beer->review_summary->body_avg?></div>
	<div>Balance:<?=$beer->review_summary->balance_avg?></div>
	<div>Aftertaste:<?=$beer->review_summary->aftertaste_avg?></div>
	<div>Style:<?=$styles->{$beer->styles[0]}->name?></div>
<?php
	}
	
	static public function brewery_html($brewery) {
		if ($brewery->photos->total):
?>
	<img src="<?=$brewery->photos->thumbnail?>" />
	<?php endif;?>
	<a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a>
	<div>Description:<?=$brewery->description?></div>
	<div>Location:<?=$brewery->address->city?>, <?=$brewery->address->state?> <?=$brewery->address->country?></div>
<?php
	}
};

$BC=new BeerCrush();

?>