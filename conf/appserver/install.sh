#!/bin/sh

# TODO: install and setup PHP's APC (opcode cache)
# TODO: Config PHP FastCGI
# TODO: install libmemcached 0.28+
# TODO: pecl install memcached (we don't yet use memcached)

if [ ! -d /etc/BeerCrush ]; then
	sudo mkdir /etc/BeerCrush;
	sudo chown www-data.www-data /etc/BeerCrush;
	sudo chmod ug+rwX /etc/BeerCrush;
fi


sudo cp fcgi.sh /etc/BeerCrush/
sudo cp spread.ini /etc/php5/conf.d/