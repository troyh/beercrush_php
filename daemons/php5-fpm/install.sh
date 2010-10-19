#!/bin/bash

. ../../config.sh;

if iamdaemon php5-fpm; then

	if [ -f /etc/php5/fpm/pool.d/www.conf ]; then
		sudo rm -f /etc/php5/fpm/pool.d/www.conf;
	fi

	. /etc/BeerCrush/daemons/php5-fpm;	

	if [ -z "$PORT" ]; then
		echo "ERROR: PORT not specified in /etc/BeerCrush/daemons/php5-fpm";
		exit 1;
	fi

	sed -e "s/_PORT_/$PORT/" beercrush.conf > /tmp/php5-fpm.conf;

	if ! files_are_identical /tmp/php5-fpm.conf /etc/php5/fpm/pool.d/beercrush.conf; then
		sudo mv /tmp/php5-fpm.conf /etc/php5/fpm/pool.d/beercrush.conf;
		sudo service php5-fpm restart;
	fi

fi

