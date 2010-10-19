#!/bin/bash

. ../../config.sh;

if ! iamdaemon php5-fpm; then
	exit;
fi

domain=$(cat $beercrush_conf_file | ../../tools/jsonpath -1 domainname);

urls=$(cat <<EOF
http://$domain/api/beerstyles
http://$domain/api/beer/Dogfish-Head-Craft-Brewery/120-Minute-IPA 

EOF
)

for url in $urls; do
	if ! curl --fail --silent $url > /dev/null; then
		cat <<EOF
ERROR: Can't connect to PHP page: $url
Either the proxy isn't working or PHP5 FPM is not installed and working.

EOF
	fi
done

