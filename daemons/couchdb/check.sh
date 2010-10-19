#!/bin/bash

. ../../config.sh;

# Find couchdb hosts
hosts=$(cat $beercrush_conf_file | OAKConfig=$beercrush_conf_file ../../tools/json2xml  | xmlstarlet sel -t -m '/obj[@tag=&quot;doc&quot;]/obj[@tag=&quot;couchdb&quot;]/array[@tag=&quot;nodes&quot;]/scalar[@tag=&quot;item&quot;]' -v @val);
dbname=$(cat $beercrush_conf_file  | ../../tools/jsonpath -1 couchdb.database);
for h in $hosts; do 
	# Verify that CouchDB is running
	if ! curl --fail --silent -D - http://$h/$dbname > /tmp/couchdb.response; then
		echo "Unable to connect to couchdb server: $h";
	else
		server=$(grep ^Server: /tmp/couchdb.response | awk '{print $2}');
		nginx_sig="^nginx/";
		if [[ $server =~ $nginx_sig ]]; then
			echo "couchdb-proxy server: $h";
		else
			echo "couchdb server: $h";
		fi
	fi
done;

