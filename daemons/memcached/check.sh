#!/bin/bash

# Verify that memcached servers are in setup.conf and webapp.conf
MC_SERVERS=$(cat /etc/BeerCrush/setup.conf | ../../tools/jsonpath -1 servers.memcached.servers);
if [ "$MC_SERVERS" = "null" ]; then
	echo "ERROR: no memcached servers set up in /etc/BeerCrush/setup.conf";
fi

MC_SERVERS=$(cat /etc/BeerCrush/webapp.conf | ../../tools/jsonpath -1 memcached.servers);
if [ "$MC_SERVERS" = "null" ]; then
	echo "ERROR: no memcached servers set up in /etc/BeerCrush/webapp.conf";
fi

cat /etc/BeerCrush/webapp.conf | 
../../tools/jsonpath -1 memcached.servers | 
php  -r '$d=json_decode(file_get_contents("php://stdin"));foreach ($d as $s) { print $s[0]." ".$s[1]."\n"; }' |
while read IP PORT; do
	
	if ! echo "stats" | netcat -q 1  $IP $PORT > /dev/null; then
		echo "ERROR: unable to connect to memcached server $IP (port $PORT)";
	fi

	MC_MEMSIZE=$(echo "stats" | netcat -q 1  $IP $PORT | grep limit_maxbytes | awk '{print $3}' | tr -d \\r);
	MC_MEMSIZE=$(( $MC_MEMSIZE / (1024*1024) ));

	if [ $MC_MEMSIZE -lt 256 ]; then
		echo "WARNING: memcached size of $MC_MEMSIZE is too small, should be at least 256";
	fi

done


