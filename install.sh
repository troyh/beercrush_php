#!/bin/bash

. ./config.sh;

if [ ! -d $BEERCRUSH_ETC_DIR ]; then
	sudo mkdir $BEERCRUSH_ETC_DIR;
fi

if [ ! -f $BEERCRUSH_ETC_DIR/webapp.conf ]; then
	echo "$BEERCRUSH_ETC_DIR/webapp.conf doesn't exist. You can get a sample from svn://beercrush/conf/appserver/webapp.conf.";
	exit 1;
fi

if [ `tools/iamservertype -q php-cgi` -o `tools/iamservertype -q web` ]; then

	if [ ! -d $WWW_DIR ]; then
		echo "Creating $WWW_DIR";
		sudo mkdir $WWW_DIR;
	fi
	
	if [ "$(ls -ld $WWW_DIR | awk '{print $3" "$4}')" != "www-data www-data" ]; then
		echo "Setting permissions on $WWW_DIR";
		sudo chown www-data.www-data $WWW_DIR;
		sudo chmod g+w $WWW_DIR;
	fi

	if tools/iamservertype -q php-cgi; then
		
		if [ ! -d $WWW_DIR/uploads ]; then
			mkdir $WWW_DIR/uploads;
		fi

		sudo chgrp $BEERCRUSH_APPSERVER_USER $WWW_DIR/uploads;
		sudo chmod g+w $WWW_DIR/uploads;
	fi

fi

if [ `tools/iamservertype -q php-cgi` -o `tools/iamservertype -q cgi` ]; then

	for DIR in  $LOCALDATA_DIR  /var/local/BeerCrush/meta/; do
		if [ ! -d $DIR ]; then
			sudo mkdir -p $DIR;
		fi
	done

	# Set correct permissions on directories
	sudo chown -R $BEERCRUSH_APPSERVER_USER.$BEERCRUSH_APPSERVER_USER /var/local/BeerCrush/;
	
fi

if tools/iamservertype -q mgmt; then

	if [ ! -d /var/run/BeerCrush ]; then
		sudo mkdir /var/run/BeerCrush;
	fi
	
	sudo chown www-data.www-data /var/run/BeerCrush;
	sudo chmod g+w /var/run/BeerCrush;
	
fi


# TODO: configure syslog.conf for OAK logging
# TODO: config logrotate for /var/log/oak.log
# TODO: install and setup PHP's APC (opcode cache)
# TODO: make autocompletenames.txt and latlonpairs.txt in /var/local/BeerCrush/meta
# TODO: Config PHP FastCGI
# TODO: turn off PHP's magic quotes
# TODO: make sure PHP's include_path is correct and uncommented in php.ini
# TODO: verify that the OAK.conf file is correct

# TODO: make this work:
# sudo pecl install spread

# TODO: install libmemcached 0.28+
# TODO: pecl install memcached
# TODO: make sure permissions are correct for $WWW_DIR and subdirs (beercrush group?)

