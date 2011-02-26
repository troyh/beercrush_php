#!/bin/bash

# Install PHP 5.3
apt-get install php5-cli;
# Make /etc/BeerCrush
if [ ! -d /etc/BeerCrush ]; then
	mkdir /etc/BeerCrush;
fi

. config.sh;

function usage()
{
	echo "Usage: $0 <app|appproxy|couchdbproxy|solrproxy|couchdb|solr> <mgmt server>";
}

case $type in 
	app)
		;;
	*)
		echo "$1 is not a valid type of server I can set up";
		usage;
		;;
esac;

