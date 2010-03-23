#!/bin/sh

# TODO: install and setup PHP's APC (opcode cache)
# TODO: Config PHP FastCGI
# TODO: install libmemcached 0.28+
# TODO: pecl install memcached (we don't yet use memcached)

sudo cp fcgi.sh /etc/BeerCrush/
sudo cp spread.ini /etc/php5/conf.d/