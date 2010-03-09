#!/bin/bash

. ../../config.sh;

if ../../tools/iamservertype -q web; then
	if [ ! -d $WWW_DIR/js/jquery-autocomplete ]; then
		echo "Making $WWW_DIR/js/jquery-autocomplete";
		mkdir $WWW_DIR/js/jquery-autocomplete;
	fi

	rsync --recursive --delete mini/*.js $WWW_DIR/js/jquery-autocomplete;
	
fi
