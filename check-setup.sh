#!/bin/bash

authapi_port=9001;
autocomplete_port=9000;
couchdb_port=5984;
couchdb_proxy_port=7000;
couchdb_database="beercrush";
memcached_port=11211;
nearby_port=9002;
nearby_beer_port=9003;
nearby_locations_port=9004;
phpcgi_port=8999;
solr_port=7007;
solr_proxy_port=7007;


myips() {
	ifconfig $e | awk '/inet addr:([0-9\.]+)/ { print substr($2,match($2,/([0-9]+)/));}'; 
}

hostisme() {
	for ip in $(myips); do
		if [ "$ip" = "$1" ]; then
			return 0;
		fi
	done
	return 1;
}

if [ ! -f /etc/BeerCrush/cluster ]; then
	echo "/etc/BeerCrush/cluster doesn't exist.";
	exit 1;
fi

hosts=$(cat /etc/BeerCrush/cluster);
for h in $hosts; do
	if hostisme $h; then
		daemons=$(ls /etc/BeerCrush/daemons/);
	else
		daemons=$(ssh $h ls /etc/BeerCrush/daemons/);
		if [ $? -ne 0 ]; then
			echo "Unable to ssh into $h";
			exit 1; 
		fi
	fi

	if [ -z "$daemons" ]; then
		echo "$h doesn't run anything!";
	else
		echo $h:;
		for d in $daemons; do
			case $d in
				authapi)
					authapi=$(echo "" | netcat $h $authapi_port);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				autocomplete)
					autocomplete=$(echo "" | netcat $h $autocomplete_port);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				autocomplete_watch)
					;;
				couchdb)
					if ! curl --fail --silent http://$h:$couchdb_port/$couchdb_database > /dev/null; then
						echo "ERROR: $d at $h is not available";
					fi
					;;
				couchdb-proxy)
					if ! curl --fail --silent http://$h:$couchdb_proxy_port/$couchdb_database > /dev/null; then
						echo "ERROR: $d at $h is not available";
					fi
					;;
				dbchanges2git)
					;;
				memcached)
					stats=$(echo "stats" | netcat $h $memcached_port);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d at $h is not available";
					fi
					# TODO: verify limit_maxbytes is high enough
					;;
				nearby)
					answer=$(echo "" | netcat $h $nearby_port);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				nearby_beer)
					answer=$(echo "" | netcat $h $nearby_beer_port);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				nearby_locations)
					answer=$(echo "" | netcat $h $nearby_locations_port);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				newphoto)
					;;
				oaklog)
					;;
				php5-fpm)
					answer=$(echo "" | netcat $h $phpcgi_port);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				solr)
					solr=$(curl --silent --fail http://$h:$solr_port/solr/);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				solr-proxy)
					solr=$(curl --silent --fail http://$h:$solr_proxy_port/solr/);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				solr_indexer)
					;;
				sendmail)
					;;
				uncache)
					;;
				web)
					# Try to access local content
					web=$(curl --silent --fail http://$h/);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					# Try to access CGI content
					web=$(curl --silent --fail http://$h/api/beerstyles);
					if [ $? -ne 0 ]; then
						echo "ERROR: $d  at $h is not available";
					fi
					;;
				*)
					echo "ERROR: Unrecognized daemon $d";
					exit 1;
					;;
			esac;
		done
	fi

done;
exit;

# Make sure all of these cron jobs are running somewhere:
# autocomplete_update  
# location  
# newbeers  
# recommend  
# similarbeers
