#!/bin/bash

. ./config.sh;

if [ ! -d $BEERCRUSH_ETC_DIR ]; then
	sudo mkdir $BEERCRUSH_ETC_DIR;
fi

if [ ! -f $BEERCRUSH_ETC_DIR/webapp.conf ]; then
	echo "$BEERCRUSH_ETC_DIR/webapp.conf doesn't exist. You can get a sample from svn://beercrush/conf/appserver/webapp.conf.";
	exit 1;
fi

if tools/iamservertype -q php-cgi || tools/iamservertype -q web; then

	if [ ! -d $WWW_DIR ]; then
		echo "Creating $WWW_DIR";
		sudo mkdir $WWW_DIR;
	fi
	
	if [ "$(ls -ld $WWW_DIR | awk '{print $3" "$4}')" != "www-data www-data" ]; then
		echo "Setting permissions on $WWW_DIR";
		sudo chown www-data.www-data $WWW_DIR;
		sudo chmod -R g+rwX $WWW_DIR;
	fi

fi

if tools/iamservertype -q php-cgi || tools/iamservertype -q cgi; then

	for DIR in  $LOCALDATA_DIR  /var/local/BeerCrush/meta/; do
		if [ ! -d $DIR ]; then
			mkdir -p $DIR;
		fi
	done

	# Set correct permissions on directories
	chgrp $BEERCRUSH_APPSERVER_USER /var/local/BeerCrush/{meta,uploads,images};
	chmod g+rwX /var/local/BeerCrush/{meta,uploads,images};
	
fi

if tools/iamservertype -q mgmt; then

	if [ ! -d /var/run/BeerCrush ]; then
		sudo mkdir /var/run/BeerCrush;
	fi
	
	sudo chown www-data.www-data /var/run/BeerCrush;
	sudo chmod g+w /var/run/BeerCrush;
	
fi


# TODO: make autocompletenames.txt and latlonpairs.txt in /var/local/BeerCrush/meta
