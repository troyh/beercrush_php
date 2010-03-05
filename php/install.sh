#!/bin/bash

. ../config.sh

if ../tools/iamservertype -q php-cgi; then

	# Note: the order of --exclude & --include matters here... (we only want non-hidden .php files)
	rsync --recursive --delete --times --exclude=".*" --include="*/" --include="*.php" --exclude="*" ./ $WWW_DIR/php/;

	R=$(svnversion -n);
	YEAR=$(date +%Y);
	for F in index.php footer.php; do
		sed -e "s/<\\!--\\s*YEAR\\s*-->/$YEAR/g" -e "s/<\\!--\\s*SVNVERSION\\s*-->/$R/g" $F > $WWW_DIR/php/$F;
	done
	
	# Delete the NGiNX cache
	rm -rf /var/local/nginx/caches/all;
	# Re-create the NGiNX cache so that we have the permissions we want (NGiNX won't change 
	# permissions on existing dirs)
	mkdir -p /var/local/nginx/caches/all;
	# We give RW group permissions so that the owner remains the user that runs this script 
	# so that they can continue to delete the cache directories.
	chgrp -R www-data /var/local/nginx/caches;
	chmod -R g+rwX /var/local/nginx/caches;

fi
