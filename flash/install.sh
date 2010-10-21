#!/bin/bash

. ../config.sh;

if iamdaemon web; then
	if [ ! -d $WWW_DIR/flash ]; then
		echo "Making $WWW_DIR/flash";
		mkdir $WWW_DIR/flash;
	fi

	rsync --recursive --delete *.swf *.fla $WWW_DIR/flash/;
	
fi
