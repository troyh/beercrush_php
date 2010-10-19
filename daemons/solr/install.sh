#!/bin/bash

. ../../config.sh;

if iamdaemon solr; then

	if [ ! -d /etc/solr ]; then
		cat - <<EOF 
Solr is not installed. Install it first:
	1. sudo apt-get install openjdk-6-jdk openjdk-6-jre solr-jetty
	2. Set NO_START=0 in /etc/default/jetty
	3. Edit /etc/jetty/jetty.xml to bind it to 0.0.0.0.

	See https://troyandgay.com/trac/projects/beerliberation/wiki/ITDocs#Solrserver for more info.

EOF
		exit 1;
	fi

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
		sudo service jetty stop;
		sudo service jetty start;
	fi

	# TODO: attempt a Solr query to prove it works
fi

