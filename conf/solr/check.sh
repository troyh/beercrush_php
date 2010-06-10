#!/bin/bash

# Attempt a Solr query to prove it works
SOLR_URL=$(php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->solr->url;');

php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/setup.conf"));foreach ($cfg->servers->solr->servers as $node) {print $node."\n";}' |
while read NODE; do
	if ! curl --silent "http://$NODE$SOLR_URL/select/?q=dogfish&wt=json" > /dev/null; then
		echo "ERROR: Solr query failed on node $NODE";
	fi
done


# TODO: verify that master_node exists in webapp.conf file
