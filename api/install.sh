#!/bin/bash

. ../config.sh

if iamdaemon php5-fpm; then

	if [ ! -d $WWW_DIR/api ]; then
		mkdir $WWW_DIR/api;
	fi
	
	if [ ! -d /var/local/BeerCrush/images ]; then
		mkdir /var/local/BeerCrush/images;
		chgrp www-data /var/local/BeerCrush/images;
		chmod g+rwX /var/local/BeerCrush/images;
	fi

	if [ ! -d /var/local/BeerCrush/uploads ]; then
		mkdir /var/local/BeerCrush/uploads;
		chgrp www-data /var/local/BeerCrush/uploads;
		chmod g+rwX /var/local/BeerCrush/uploads;
	fi

	# Note: the order of --exclude & --include matters here... (we only want non-hidden .php files)
	rsync --recursive --delete --times --exclude=".*" --include="*/" --include="*.php" --exclude="*" ./ $WWW_DIR/api/;
	
	# NOTE: This needs to not require sudo because this script will run on development servers by incron for every change
	# to a file and it can't answer a prompt for root's password
	#
	# Delete the NGiNX cache
	rm -rf /var/local/nginx/caches/api;
	# Re-create the NGiNX cache so that we have the permissions we want (NGiNX won't change 
	# permissions on existing dirs)
	mkdir -p /var/local/nginx/caches/api;
	# We give RW group permissions so that the owner remains the user that runs this script 
	# so that they can continue to delete the cache directories.
	chgrp www-data /var/local/nginx/caches/api;
	chmod g+rwX /var/local/nginx/caches/api;
	
fi

