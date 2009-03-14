#!/bin/bash

if [ "$1" == "" ]; then
	MINDEPTH=2;
else
	MINDEPTH=$1;
fi

find . -mindepth $MINDEPTH -type f -name "*.xml" | while read F; do
	xmlstarlet fo --encode utf-8 $F > $F.new;
	if diff $F $F.new > /dev/null; then
		rm $F.new;
	else
		echo "Formatted: $F";
		mv $F.new $F
	fi
done
