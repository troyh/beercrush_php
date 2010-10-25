#!/bin/bash

. ../../config.sh;

if ! iamdaemon web ; then
	exit;
fi

# Make sure NGiNX is installed
if [ ! -d /etc/nginx ]; then
	echo "NGiNX 0.8.29+ is not installed. You must build it manually.";
	exit 1;
fi

if [ ! -f "/etc/BeerCrush/setup.conf" ]; then
	echo "Can't find /etc/BeerCrush/setup.conf";
	exit 1;
fi

if [ -f /tmp/hosts.sed ]; then
	rm /tmp/hosts.sed;
fi

# Get the hosts for CGI
for type in app autocomplete auth nearby nearbybeer nearbyloc; do
	host=$(cat /etc/BeerCrush/setup.conf | php -r "\$cfg=json_decode(file_get_contents(\"php://stdin\")); foreach (\$cfg->servers->$type as \$h) { print \"\$h\\n\"; }" ); 
	echo "s/_${type}_host_/$host/" >> /tmp/hosts.sed;
done

sed -f /tmp/hosts.sed beercrush.conf > /tmp/beercrush.conf;
rm -f /tmp/hosts.sed

restart_nginx=false;

if ! files_are_identical nginx.conf /etc/nginx/nginx.conf; then
	sudo cp nginx.conf /etc/nginx/nginx.conf
	restart_nginx=true;
fi

if ! files_are_identical beercrush-urls /etc/nginx/beercrush-urls; then
	sudo cp beercrush-urls /etc/nginx/beercrush-urls;
	restart_nginx=true;
fi

if ! files_are_identical /tmp/beercrush.conf /etc/nginx/sites-available/beercrush; then
	sudo cp /tmp/beercrush.conf /etc/nginx/sites-available/beercrush;
	restart_nginx=true;
fi

if [ ! -h /etc/nginx/sites-enabled/beercrush ]; then
	sudo ln -s /etc/nginx/sites-available/beercrush /etc/nginx/sites-enabled/beercrush;
	restart_nginx=true;
fi

for D in /etc/nginx/sites-available /etc/nginx/sites-enabled /var/log/nginx/ /var/local/nginx/ /var/local/nginx/caches; do
	if [ ! -d $D ]; then
		sudo mkdir $D;
	fi
done

# Clear out caches
sudo rm -rf /var/local/nginx/caches/all /var/local/nginx/caches/api;

for cache_dir in all api; do
	if [ ! -d /var/local/nginx/caches/$cache_dir ]; then
		sudo mkdir -p /var/local/nginx/caches/$cache_dir;
	fi

	# We give RW group permissions so that the owner remains the user that runs this script 
	# so that they can continue to delete the cache directories.
	sudo chgrp www-data /var/local/nginx/caches/$cache_dir;
	sudo chmod g+rwX /var/local/nginx/caches/$cache_dir;

done

if [ $restart_nginx = true ]; then
	sudo service nginx restart;
fi

