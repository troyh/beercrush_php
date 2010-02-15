#!/bin/bash

. ../config.sh;

if ../tools/iamservertype -q web; then
	if [ ! -d $WWW_DIR/js ]; then
		echo "Making $WWW_DIR/js";
		mkdir $WWW_DIR/js;
	fi

	rsync --recursive --delete mini/*.js $WWW_DIR/js/;
	
fi
