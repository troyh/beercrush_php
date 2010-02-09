#!/bin/bash

if ! diff -u conf/nginx/couchdb.conf /etc/nginx/sites-enabled/couchdb > /dev/null; then
	echo "**************************************************";
	echo "ERROR: NGiNX proxy for CouchDB config (/etc/nginx/sites-enabled/couchdb) is incorrect:";
	echo "**************************************************";
	diff -u conf/nginx/couchdb.conf /etc/nginx/sites-enabled/couchdb;
	exit;
fi

