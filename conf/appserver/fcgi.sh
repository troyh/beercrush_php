#!/bin/sh
# Configuration directory
# How much children per manager
#export PHP_FCGI_CHILDREN=4
# How much requests should be queued per child
#export PHP_FCGI_MAX_REQUESTS=5
# What binary to run with above settings
export OAKConfig=/etc/BeerCrush/webapp.conf

exec /usr/bin/php5-cgi
