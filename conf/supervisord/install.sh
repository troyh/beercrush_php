#!/bin/bash

. ../../config.sh

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

