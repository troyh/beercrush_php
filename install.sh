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

#
# Make sure that Spread 4.1.0 is installed
#
if ! echo "q" | spuser | head -1 | grep "Spread library version is 4.1.0" > /dev/null; then
	cat - <<EOF
The Spread Toolkit 4.1.0 must be installed. To do that:

	cd src/3rdparty 
	tar xvzf spread-src-4.1.0.tar.gz 
	cd spread-src-4.1.0/
	./configure
	make
	sudo make install
	sudo ldconfig
	sudo rm -rf /usr/lib/libspread.* /usr/lib/libtspread.*

EOF
	exit 1;
fi;

#
# Make sure that the PHP Spread extension is installed
#
if php -r 'if (function_exists(spread_connect)) exit(1);'; then 
	cat - <<EOF
The Spread PHP extension must be installed. To do that:

	cd src/3rdparty/
	tar xvzf spread-2.1.0.tgz 
	cd spread-2.1.0/
	phpize
	./configure 
	make
	sudo make install

Add extension=spread.so to PHP .ini files:

	/etc/php5/cli/php.ini
	/etc/php5/cgi/php.ini

EOF

	exit 2;

fi

