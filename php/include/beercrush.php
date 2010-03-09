<?php
require_once('OAK/oak.class.php');

class BeerCrush
{
	// Useful Defines
	const DATE_FORMAT='D, d M Y H:i:s O';
	const DEFAULT_AVATAR_URL="/img/default_avatar.gif";
	const CONF_FILE='/etc/BeerCrush/webapp.conf';
	const SETUP_CONF='/etc/BeerCrush/setup.conf';
	
	static function api_doc($oak,$url)
	{
		return json_decode(@file_get_contents($oak->get_config_info()->api->base_uri.'/'.ltrim($url,'/')));
	}
};

?>