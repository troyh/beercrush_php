#!/bin/bash

# TODO: verify /etc/tomcat6/tomcat-users.xml is correct
# TODO: copy the BeerCrush conf/solr/schema.xml to /etc/solr/conf/schema.xml
# TODO: copy the BeerCrush conf/solr/solrconfig.xml to /etc/solr/conf/solrconfig.xml
# TODO: attempt a Solr query to prove it works

if ../../tools/iamservertype -q solr; then

	if ! diff -u conf/solr/solrconfig.xml /etc/solr/conf/solrconfig.xml > /dev/null; then
		echo "**************************************************";
		echo "ERROR: Solr config (/etc/solr/conf/solrconfig.xml) is incorrect:";
		echo "**************************************************";
		diff -u conf/solr/solrconfig.xml /etc/solr/conf/solrconfig.xml;
		exit;
	fi

	if ! diff -u conf/solr/schema.xml /etc/solr/conf/schema.xml > /dev/null; then
		echo "**************************************************";
		echo "ERROR: Solr schema (/etc/solr/conf/schema.xml) is incorrect:";
		echo "**************************************************";
		diff -u conf/solr/schema.xml /etc/solr/conf/schema.xml;
		exit;
	fi

fi
