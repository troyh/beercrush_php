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
	exit 1;
fi

# Verify that supervisord is running
SUPERVISORD_PIDFILE="/var/run/supervisord.pid"
if ! test -f $SUPERVISORD_PIDFILE || ! ps -p $(cat $SUPERVISORD_PIDFILE) > /dev/null; then
	echo "ERROR: supervisord is not running.";
	exit 1;
	# Verify that all supervisord services are running
elif sudo supervisorctl status|awk '{print $2}'|grep -v RUNNING > /dev/null; then
	echo "ERROR: One or more supervisord services are not running:";
	sudo supervisorctl status;
	exit 1;
fi
