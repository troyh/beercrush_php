#!/bin/bash

. ../../config.sh

PATH=$PATH:$BEERCRUSH_BIN_DIR

if ../../tools/iamservertype -q cgi || ../../tools/iamservertype -q php-cgi; then
	
	sudo cp appserver.conf /etc/supervisor/conf.d/;
	
fi

if ../../tools/iamservertype -q php-cgi; then

	sudo cp php-cgi.conf /etc/supervisor/conf.d/;
	
fi

if ../../tools/iamservertype -q web; then

	sudo cp web.conf /etc/supervisor/conf.d/;
	
fi

if ../../tools/iamservertype -q couchdb-proxy; then

	sudo cp couchdb-proxy.conf /etc/supervisor/conf.d/;
	
fi

if ../../tools/iamservertype -q gitrepo; then

	cp ../../spread/tools/listen /usr/local/beercrush/bin/listen;
	sudo cp dbchanges2git.conf /etc/supervisor/conf.d/;
	
fi

if iamdaemon -q solr_indexer; then
	
	sudo cp solr_indexer.conf /etc/supervisor/conf.d/;
	
fi
