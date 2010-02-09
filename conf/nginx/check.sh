#!/bin/bash

# Test the config
if ! /etc/init.d/nginx configtest; then
	echo "Please fix NGiNX config errors above.";
	exit;
fi

if ../../tools/iamservertype -q couchdb-proxy; then

	# Test that the CouchDB proxy works
	if [ `curl --silent  -D  -  http://localhost:7000/beercrush/ |head -n 1 | awk '{print $2}'` != "200" ]; then
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
