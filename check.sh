#!/bin/bash

# TODO: make sure permissions are correct for $WWW_DIR and subdirs (beercrush group?)
# TODO: Verify /etc/BeerCrush/setup.conf is correct
# TODO: Verify /etc/BeerCrush/webapp.conf is correct
# TODO: Test network routes
# TODO: Verify servers are running (solr, nginx, fcgi, etc)
# TODO: File permissions are correct

UMASK=$(grep umask /etc/profile |awk '{print $2}');
if [ $UMASK -ne "002" ]; then
	echo "ERROR: umask is $UMASK, but must be 002. Edit /etc/profile.";
	
fi

