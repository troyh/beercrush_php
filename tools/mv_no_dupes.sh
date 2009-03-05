#!/bin/bash

SRC=$1;
DEST=$2;

for D in `ls $SRC/`; do
	
	for F in `ls $SRC/$D/`; do
		if [ -f $DEST/$D/$F ]; then 
			# File is there, see if it's the same
			if ! diff $SRC/$D/$F $DEST/$D/$F > /dev/null; then
				echo "Different: $SRC/$D/$F $DEST/$D/$F"; 
			else
				# File is identical, delete this one
				rm $SRC/$D/$F
			fi
		else
			if [ ! -d $DEST/$D ]; then
				mkdir $DEST/$D;
			fi
			mv -i $SRC/$D/$F $DEST/$D/$F
		fi
	done

done

# Remove any empty dirs in $SRC
find $SRC -type d | while read D; do
	COUNT=`ls $D | wc -l`;
	if [ $COUNT -eq 0 ]; then
		rmdir $D
	fi
done
