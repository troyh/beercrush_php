#!/bin/bash

DIR=$1;
ROOT_XPATH=$2

if [ "$DIR" == "" -o "$ROOT_XPATH" == "" ]; then
	echo "Usage: $0 <XML files directory> <XPath to parent element of address element>";
	exit;
fi

YAHOO_APPID="Js7UyWzV34GqmZhkyjBywhOiADH0UJtqcXMe4eZbJ4AIQZZllGGkZv2832La_Dipag--";

find $DIR/ -type f -name '*.xml' |
while read XML_FILE; do

	if [ ! -s "$XML_FILE" ]; then
		# echo "$XML_FILE is zero-size";
		continue;
	fi

	# echo $XML_FILE

    STREET=$(xmlstarlet sel -t -m "$ROOT_XPATH" -v "address/street" "$XML_FILE" | sed -e '/$/N;s/\n/ /g');
    CITY=$(xmlstarlet   sel -t -m "$ROOT_XPATH" -v "address/city"   "$XML_FILE" | sed -e '/$/N;s/\n/ /g');
    STATE=$(xmlstarlet  sel -t -m "$ROOT_XPATH" -v "address/state"  "$XML_FILE" | sed -e '/$/N;s/\n/ /g');

	if [ -z "$STREET" -o -z "$CITY" -o -z "$STATE" ]; then
		# No street, city and/or state, we're not going to be able to get the long/lat and zip for this one
		continue;
	fi
	
	# if [ -z "$(echo $STREET | sed -e 's/^ *[0-9][0-9]* *//')" ]; then
	# 	# Fix the address problem, the street is divided between STREET and CITY and the city is in STATE
	# 	STREET="$STREET $CITY";
	# 	CITY="$STATE";
	# 	# Get the state from the last 2 chars of the filename
	# 	STATE=$(basename "$XML_FILE" .xml | sed -e 's/^.*\([A-Z][A-Z]\)$/\1/');
	# fi

	# URL-encode the Street and City names
	STREET_URL=$(echo $STREET | sed -e 's/ /\+/g' -e 's/ *#.*$//');
	CITY_URL=$(echo $CITY     | sed -e 's/ /\+/g');
	STATE_URL=$(echo $STATE   | sed -e 's/ /\+/g');

	echo "---------------------"
	echo $XML_FILE
	echo "---------------------"
	echo $STREET
	echo $CITY, $STATE
	# echo $STREET_URL
	# echo $CITY_URL $STATE

	read LAT LON ZIP COUNTRY <<< $(
		curl --silent "http://local.yahooapis.com/MapsService/V1/geocode?appid=$YAHOO_APPID&street=$STREET_URL&city=$CITY_URL&state=$STATE_URL" |
		xmlstarlet sel -N yapi="urn:yahoo:maps" -t -m "/yapi:ResultSet/yapi:Result[1]" \
			-v "yapi:Latitude" -o "&#09;" \
		 	-v "yapi:Longitude" -o "&#09;" \
			-v "yapi:Zip" -o "&#09;" \
			-v "yapi:Country" -n
	)

	echo LAT=$LAT
	echo LON=$LON
	echo ZIP=$ZIP
	echo COUNTRY=$COUNTRY
	echo "---------------------"

	xmlstarlet ed 	--delete "$ROOT_XPATH/address/zip" \
					--delete "$ROOT_XPATH/address/latitude" \
					--delete "$ROOT_XPATH/address/longitude" \
					--delete "$ROOT_XPATH/address/country" \
					--update "$ROOT_XPATH/address/street" -v "$STREET" \
					--update "$ROOT_XPATH/address/city"   -v "$CITY" \
					--update "$ROOT_XPATH/address/state"  -v "$STATE" \
					--subnode "$ROOT_XPATH/address" -t elem -n "zip" -v "$ZIP" \
					--subnode "$ROOT_XPATH/address" -t elem -n "latitude" -v "$LAT" \
					--subnode "$ROOT_XPATH/address" -t elem -n "longitude" -v "$LON" \
					--subnode "$ROOT_XPATH/address" -t elem -n "country" -v "$COUNTRY" \
					"$XML_FILE" > "$XML_FILE.new"

	if [ -s "$XML_FILE.new" ]; then
		mv "$XML_FILE.new" "$XML_FILE";
	else
		echo "Failed to edit $XML_FILE";
	fi

done
	