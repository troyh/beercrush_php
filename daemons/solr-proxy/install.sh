#!/bin/bash

. ../../config.sh;

if iamdaemon solr-proxy; then

	solr_config_changed=false;

	if ! files_are_identical solr.conf /etc/nginx/sites-available/solr; then
		sudo cp solr.conf /etc/nginx/sites-available/solr;
		solr_config_changed=true;
	fi

	if [ ! -h /etc/nginx/sites-enabled/solr ]; then
		sudo ln -s /etc/nginx/sites-available/solr /etc/nginx/sites-enabled/solr;
		solr_config_changed=true;
	fi
	
	if [ ! -d /var/local/nginx/caches/solr/ ]; then
		sudo mkdir -p /var/local/nginx/caches/solr/;
		sudo chown www-data.www-data /var/local/nginx/caches/solr/;
		solr_config_changed=true;
	fi

	if [[ $solr_config_changed = true ]]; then
		sudo service nginx restart;
	fi

fi

