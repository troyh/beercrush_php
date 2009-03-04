#!/bin/bash

SRC=$1;
DEST=$2;

for D in `ls $SRC/`; do
	
	for F in `ls $SRC/$D/`; do
		# echo "Testing $F";
		if [ -f $DEST/$D/$F ]; then 
			echo "Dupes: $SRC/$D/$F $DEST/$D/$F"; 
		fi
	done

done
