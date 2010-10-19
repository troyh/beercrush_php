#!/bin/bash

. ../../config.sh;

if iamdaemon couchdb-proxy; then

	if [ ! -e /etc/nginx ]; then
		cat - <<EOF
ERROR: NGiNX isn't installed. Install it first:
See https://troyandgay.com/trac/projects/beerliberation/wiki/InstallNGiNX

EOF
		exit 1;
	fi

	nginx_ver=$(/usr/sbin/nginx -v 2>&1); # nginx -v writes to stderr for some reason
	re="nginx version: nginx/0.8.([0-9]+)";
	if [[ $nginx_ver =~ $re && ${BASH_REMATCH[1]} > 26 ]]; then
		echo "NGiNX version: $nginx_ver";
	else
		cat - <<EOF
ERROR: You must use NGiNX 0.8.26 or better. You're using $nginx_ver.
	
EOF
		exit 1;
	fi

	for D in /etc/nginx/sites-available /etc/nginx/sites-enabled /var/log/nginx/ /var/local/nginx/ /var/local/nginx/caches; do
		if [ ! -d $D ]; then
			sudo mkdir $D;
		fi
	done

	read proxyport host port <<< $(../../tools/jsonpath -1 proxy.port couchdb.host couchdb.port < /etc/BeerCrush/daemons/couchdb-proxy) ;
	if [ -z "$host" ]; then
		cat - <<EOF
ERROR: The CouchDB host is not specified in /etc/BeerCrush/daemons/couchdb-proxy

EOF
		exit 1;
	fi

	if [ -z "$port" ]; then
		port=5984;
	fi

	if [ -z "$proxyport" ]; then
		proxyport=7000;
	fi

	cfg_changed=false;

	sed -e "s/PROXYPORT/$proxyport/" -e "s/HOSTNAME/$host/" -e "s/PORT/$port/" couchdb-proxy.conf > /tmp/couchdb-proxy.conf;
	if ! files_are_identical /etc/nginx/sites-available/couchdb-proxy.conf /tmp/couchdb-proxy.conf; then 
		sudo mv /tmp/couchdb-proxy.conf /etc/nginx/sites-available/couchdb-proxy.conf 
		cfg_changed=true;
	fi

	if (! test -L /etc/nginx/sites-enabled/couchdb-proxy.conf) || ( ! files_are_identical /etc/nginx/sites-available/couchdb-proxy.conf /etc/nginx/sites-enabled/couchdb-proxy.conf ); then
		sudo ln -f -s /etc/nginx/sites-available/couchdb-proxy.conf /etc/nginx/sites-enabled/couchdb-proxy.conf
		cfg_changed=true;
	fi

	if [ $cfg_changed = true ]; then
		echo "NGiNX configuration changed.";
		sudo service nginx restart
	fi

	# Test that the CouchDB proxy works
	if [ "`curl --silent  -D  -  http://localhost:$proxyport/beercrush/ |head -n 1 | awk '{print $2}'`" != "200" ]; then
		echo "Unable to access couchdb through proxy (http://localhost:$proxyport/beercrush/)";
		exit 1;
	else
		echo "couchdb-proxy is installed and works";
	fi

fi

