#!/bin/bash

. ../config.sh

if ../tools/iamservertype -q php-cgi; then

	if [ ! -d $WWW_DIR/api ]; then
		mkdir $WWW_DIR/api;
	fi
	
	if [ ! -d /var/local/BeerCrush/images ]; then
		mkdir /var/local/BeerCrush/images;
		sudo chown www-data.www-data /var/local/BeerCrush/images;
		sudo chmod -R g+rwx /var/local/BeerCrush/images;
	fi

	# Note: the order of --exclude & --include matters here... (we only want non-hidden .php files)
	rsync --recursive --delete --times --exclude=".*" --include="*/" --include="*.php" --exclude="*" ./ $WWW_DIR/api/;
	
	# Delete the NGiNX cache
	sudo rm -rf /var/local/nginx/caches/api;
	
fi
