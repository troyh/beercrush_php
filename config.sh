#!/bin/bash

BEERCRUSH_ETC_DIR="/etc/BeerCrush/"
BEERCRUSH_BIN_DIR="/usr/local/beercrush/bin/"
BEERCRUSH_PHPINC_DIR="/usr/share/php/beercrush/"
BEERCRUSH_SOURCE_DIR="$HOME/beercrush/"
BEERCRUSH_APPSERVER_USER=www-data

beercrush_conf_file="/etc/BeerCrush/webapp.conf";

PATH=$PATH:$BEERCRUSH_BIN_DIR

WWW_DIR=$(php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->file_locations->WWW_DIR."\n";');

if [ -z "$WWW_DIR" ]; then
	echo "WWW_DIR is empty!"
	exit;
fi

SUBVERSION_URL=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/setup.conf"));print $cfg->subversion->url."\n";'`;
MGMT_SERVER=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/setup.conf"));print $cfg->servers->mgmt->servers[0]."\n";'`;
SITE_DOMAIN_NAME=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->domainname."\n";'`;
WWW_DIR=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->file_locations->WWW_DIR."\n";'`;
LOCALDATA_DIR=`php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->file_locations->LOCAL_DIR."\n";'`;

for D in $WWW_DIR $BEERCRUSH_BIN_DIR $BEERCRUSH_ETC_DIR/cron $BEERCRUSH_ETC_DIR/daemons $LOCALDATA_DIR; do
	if [ ! -d $D ]; then
		sudo mkdir -p $D;
		sudo chgrp $BEERCRUSH_APPSERVER_USER $D;
		sudo chmod g+rw $D;
	fi
done


iamcron() {
	if ls $BEERCRUSH_ETC_DIR/cron/ | grep $1 > /dev/null; then
		return 0;
	fi
	return 1;
}

iamdaemon() {
	if ls $BEERCRUSH_ETC_DIR/daemons/ | grep $1 > /dev/null; then
		return 0;
	fi
	return 1;
}

files_are_identical() {
	if [ ! -f $1 -o ! -f $2 ]; then
		return 1;
	fi
	
	N=$(md5sum $1 $2 | cut -f 1 -d ' ' | sort -u | wc -l);
	if [[ $N != 1 ]]; then
		return 1;
	fi
	
	return 0; # The files are identical
}

my_ip_addresses() {
	echo $(ifconfig $e | awk '/inet addr:([0-9\.]+)/ { print substr($2,match($2,/([0-9]+)/));}');
}

start_or_restart_service() {
	if sudo service $1 status > /dev/null; then
		if [ "$1" = "supervisor" ]; then
			# supervisord doesn't restart, you have to stop and then start it
			sudo service $1 stop;
			sleep 5;
			sudo service $1 start;
		else
			sudo service $1 restart;
		fi
	else
		sudo service $1 start;
	fi
}

