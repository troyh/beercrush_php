#!/bin/bash

. ../config.sh

if ../tools/iamservertype -q php-cgi; then

	sudo cp php-cgi /usr/local/beercrush/spread-php-cgi;
	
fi

if ../tools/iamservertype -q web; then

	sudo cp web /usr/local/beercrush/spread-web;
	
fi

if ../tools/iamservertype -q couchdb-proxy; then

	sudo cp couchdb-proxy /usr/local/beercrush/spread-couchdb-proxy;
	
fi

# Copy oaklog to all machine types so it's always available
sudo cp oaklog /usr/local/bin/

