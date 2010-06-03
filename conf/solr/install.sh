#!/bin/bash

. ../../config.sh;

# TODO: verify /etc/tomcat6/tomcat-users.xml is correct
# TODO: copy the BeerCrush conf/solr/schema.xml to /etc/solr/conf/schema.xml
# TODO: copy the BeerCrush conf/solr/solrconfig.xml to /etc/solr/conf/solrconfig.xml
# TODO: attempt a Solr query to prove it works

if iamservertype -q solr; then

	solr_config_changed=0;

	if ! files_are_identical solrconfig.xml /etc/solr/conf/solrconfig.xml; then
		echo "Installing Solr solrconfig.xml";
		sudo cp solrconfig.xml /etc/solr/conf/solrconfig.xml;
		solr_config_changed=1;
	fi

	if ! files_are_identical schema.xml /etc/solr/conf/schema.xml; then
		echo "Installing Solr schema.xml";
		sudo cp schema.xml /etc/solr/conf/schema.xml;
		solr_config_changed=1;
	fi
	
	if [ $solr_config_changed -ne 0 ]; then
		echo "Solr config changed. Restarting Solr...";
		sudo /etc/init.d/jetty stop;
		sudo /etc/init.d/jetty start;
	fi

fi
