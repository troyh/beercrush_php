#!/bin/bash

# Test the config
if [ ! sudo /etc/init.d/nginx configtest 2> /dev/null ]; then
	echo "Please fix NGiNX config errors above.";
	exit 1;
fi

if ../../tools/iamservertype -q couchdb-proxy; then

	if ! diff -u couchdb.conf /etc/nginx/sites-enabled/couchdb > /dev/null; then
		echo "**************************************************";
		echo "ERROR: NGiNX proxy for CouchDB config (/etc/nginx/sites-enabled/couchdb) is incorrect:";
		echo "**************************************************";
		diff -u couchdb.conf /etc/nginx/sites-enabled/couchdb;
		exit 1;
	fi

	# Test that the CouchDB proxy works
	if [ "`curl --silent  -D  -  http://localhost:7000/beercrush/ |head -n 1 | awk '{print $2}'`" != "200" ]; then
		echo "Unable to access couchdb through proxy (http://localhost:7000/beercrush/)";
		exit 1;
	fi
	
fi

if ../../tools/iamservertype -q solr-proxy; then

	# Test that the Solr proxy works
	if [ `curl --silent  -D  -  http://localhost:7007/solr/ |head -n 1 | awk '{print $2}'` != "200" ]; then
		echo "Unable to access Solr through proxy (http://localhost:7007/solr/)";
		exit;
	fi

fi

if ../../tools/iamservertype -q couchdb-proxy || ../../tools/iamservertype -q solr-proxy || ../../tools/iamservertype -q web; then

	NGINX_VER=`/usr/sbin/nginx -v 2>&1`; # nginx -v writes to stderr for some reason
	if [ "$NGINX_VER" != "nginx version: nginx/0.8.26" ]; then
		cat - <<EOF
	You must use NGiNX 0.8.26. You're using $NGINX_VER.
	
	To build it:

	tar xvzf nginx-0.8.26.tar.gz
	tar xvzf ngx_cache_purge.tar.gz
	cd nginx-0.8.26/
	./configure --prefix=/usr  --sbin-path=/usr/sbin/nginx --conf-path=/etc/nginx/nginx.conf --error-log-path=/var/log/nginx/error.log --pid-path=/var/run/nginx.pid --user=www-data --group=www-data --add-module=../ngx_cache_purge/
	make
	sudo make install

EOF

	fi

fi