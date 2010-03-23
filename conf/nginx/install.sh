#!/bin/bash

if ../../tools/iamservertype -q web ||  ../../tools/iamservertype -q couchdb-proxy ||  ../../tools/iamservertype -q solr-proxy ; then
    # Make sure NGiNX is installed
    if [ ! -d /etc/nginx ]; then
	echo "NGiNX 0.8.29 is not installed. You must build it manually.";
	exit 1;
    fi

    sudo cp nginx.conf /etc/nginx/nginx.conf
fi

if ../../tools/iamservertype -q web; then

	sudo cp beercrush-urls /etc/nginx/beercrush-urls;
	sudo cp wwwserver.conf /etc/nginx/sites-available/beercrush;
	if [ ! -h /etc/nginx/sites-enabled/beercrush ]; then
		sudo ln -s /etc/nginx/sites-available/beercrush /etc/nginx/sites-enabled/beercrush;
	fi
	

fi

if ../../tools/iamservertype -q solr-proxy; then

	sudo cp solr.conf /etc/nginx/sites-available/solr;
	if [ ! -h /etc/nginx/sites-enabled/solr ]; then
		sudo ln -s /etc/nginx/sites-available/solr /etc/nginx/sites-enabled/solr;
	fi
	
	if [ ! -d /var/local/nginx-solr/ ]; then
		sudo mkdir /var/local/nginx-solr/;
		sudo chown www-data.www-data /var/local/nginx-solr/;
	fi

fi

if ../../tools/iamservertype -q couchdb-proxy; then

	sudo cp couchdb.conf /etc/nginx/sites-available/couchdb;
	if [ ! -h /etc/nginx/sites-enabled/couchdb ]; then
		sudo ln -s /etc/nginx/sites-available/couchdb /etc/nginx/sites-enabled/couchdb;
	fi

fi

if ../../tools/iamservertype -q web ||  ../../tools/iamservertype -q couchdb-proxy ||  ../../tools/iamservertype -q solr-proxy ; then
	# Restart NGiNX (we've probably copied an NGiNX config file or two above).
	#
	# We do this separately here rather than in each block above because one host can be multiple NGiNX server types
	# and we want to restart NGiNX just once (just to not be dumb, not because it's required).
	sudo /etc/init.d/nginx restart;
fi

	sudo rm -rf /var/local/nginx/caches;
	# Make  the directory for caches
	mkdir -p /var/local/nginx/caches/all;
	mkdir -p /var/local/nginx/caches/api;
	mkdir -p /var/local/nginx/caches/couchdb; 
	# We give RW group permissions so that the owner remains the user that runs this script 
	# so that they can continue to delete the cache directories.
	chgrp www-data /var/local/nginx/caches/all;
	chgrp www-data /var/local/nginx/caches/api;
	chgrp www-data /var/local/nginx/caches/couchdb;
	chmod g+rwX /var/local/nginx/caches/all;
	chmod g+rwX /var/local/nginx/caches/api;
	chmod g+rwX /var/local/nginx/caches/couchdb;
