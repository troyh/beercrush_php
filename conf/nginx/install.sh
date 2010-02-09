#!/bin/bash

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

if [ `../../tools/iamservertype -q web` -o `../../tools/iamservertype -q couchdb-proxy` -o `../../tools/iamservertype -q solr-proxy` ]; then
	# Restart NGiNX (we've probably copied an NGiNX config file or two above).
	#
	# We do this separately here rather than in each block above because one host can be multiple NGiNX server types
	# and we want to restart NGiNX just once (just to not be dumb, not because it's required).
	sudo /etc/init.d/nginx restart;
fi

