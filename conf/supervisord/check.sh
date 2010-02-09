#!/bin/bash

###############################################
# Supervisord
###############################################

# Verify that supervisord is config'd
if [ ! -f /etc/supervisor/conf.d/BeerCrush-app.conf ]; then
	echo "I don't see /etc/supervisor/conf.d/BeerCrush-app.conf. You can get one from svn://beercrush/conf/supervisord/appserver.conf."
	exit 1;
fi

MYIP=`/sbin/ifconfig eth0 |grep "inet addr:" | sed -e 's/inet addr://' |  awk '{print $1}'`;
if ! sed -e "s/MYIPADDRESS/$MYIP/" appserver.conf | diff -u - /etc/supervisor/conf.d/BeerCrush-app.conf > /dev/null; then
	echo "**************************************************";
	echo "ERROR: Supervisor config (/etc/supervisor/conf.d/BeerCrush-app.conf) is incorrect:";
	echo "**************************************************";
	sed -e "s/MYIPADDRESS/$MYIP/" appserver.conf | diff -u - /etc/supervisor/conf.d/BeerCrush-app.conf;
	exit 1;
fi

# Verify that /var/run/supervisor exists
if [ ! -d /var/run/supervisor ]; then
	echo "Creating /var/run/supervisor";
	sudo mkdir /var/run/supervisor
fi
