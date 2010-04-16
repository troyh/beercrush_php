#!/bin/bash
. $(dirname $0)/../config.sh

PATH=$PATH:$BEERCRUSH_BIN_DIR

# Verify that conf has a Solr master node
SOLR_MASTER=$(cat /etc/BeerCrush/webapp.conf | jsonpath -1 solr.master_node);
SOLR_URL=$(cat /etc/BeerCrush/webapp.conf | jsonpath -1 solr.url);

if [ -z "$SOLR_MASTER" ]; then
	echo "ERROR: solr.master_node is not set in webapp.conf";
elif [ -z "$SOLR_URL" ]; then
	echo "ERROR: solr.url is not set in webapp.conf";
else
	# Test the Solr master to see if it works
	if ! curl --silent "http://$SOLR_MASTER$SOLR_URL/" > /dev/null; then
		echo "ERROR: Unable to connect to Solr master $SOLR_MASTER";
	fi
fi

# Try to connect to all Solr nodes
php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));foreach ($cfg->solr->nodes as $node) {print "$node\n";}' |
while read NODE; do
	if ! curl --silent "http://$NODE$SOLR_URL/" > /dev/null; then
		echo "ERROR: Unable to connect to Solr node $NODE";
	fi
done
