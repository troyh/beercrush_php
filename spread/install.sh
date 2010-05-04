#!/bin/bash

. ../config.sh

PATH=$PATH:$BEERCRUSH_BIN_DIR

if iamservertype -q web; then

	sudo cp web /usr/local/beercrush/spread-web;
	
fi

if iamservertype -q couchdb-proxy; then

	sudo cp couchdb-proxy /usr/local/beercrush/spread-couchdb-proxy;
	
fi

for D in solr_indexer; do
	
	if iamdaemon -q $D; then
		sudo cp $D /usr/local/beercrush/bin;
	fi
	
done

