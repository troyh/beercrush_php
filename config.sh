#!/bin/bash

WWW_DIR=$(php -r '$$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $$cfg->file_locations->WWW_DIR."\n";');

if [ -z "$WWW_DIR" ]; then
	echo "WWW_DIR is empty!"
	exit;
fi

BEERCRUSH_ETC_DIR="/etc/BeerCrush/"
BEERCRUSH_BIN_DIR="/usr/local/beercrush/bin/"
BEERCRUSH_PHPINC_DIR="/usr/share/php/beercrush/"
BEERCRUSH_SOURCE_DIR="$HOME/beercrush/"
BEERCRUSH_APPSERVER_USER=www-data

SUBVERSION_URL=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/setup.conf"));print $cfg->subversion->url."\n";'`;
MGMT_SERVER=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/setup.conf"));print $cfg->servers->mgmt->servers[0]."\n";'`;
SITE_DOMAIN_NAME=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->domainname."\n";'`;
WWW_DIR=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->file_locations->WWW_DIR."\n";'`;
LOCALDATA_DIR=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->file_locations->LOCAL_DIR."\n";'`;

