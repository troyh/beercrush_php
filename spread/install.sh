#!/bin/bash

. ../config.sh

PATH=$PATH:$BEERCRUSH_BIN_DIR

if iamservertype -q php-cgi; then

	sudo cp php-cgi /usr/local/beercrush/spread-php-cgi;
	
fi

if iamservertype -q web; then

	sudo cp web /usr/local/beercrush/spread-web;
	
fi

if iamservertype -q couchdb-proxy; then

	sudo cp couchdb-proxy /usr/local/beercrush/spread-couchdb-proxy;
	
fi

# Copy oaklog to all machine types so it's always available
sudo cp oaklog /usr/local/bin/

for D in solr_indexer; do
	
	if iamdaemon -q $D; then
		sudo cp $D /usr/local/beercrush/bin;
	fi
	
done

