#!/bin/bash

if ../../tools/iamservertype -q couchdb; then

	if [ ! -d /var/local/nginx-couchdb/ ]; then
		sudo mkdir /var/local/nginx-couchdb/;
		sudo chown www-data.www-data /var/local/nginx-couchdb/;
	fi

fi
