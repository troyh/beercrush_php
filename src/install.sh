#!/bin/bash

. ../config.sh

if ../tools/iamservertype -q cgi; then

	if [ ! -d $WWW_DIR/api ]; then
		mkdir $WWW_DIR/api;
	fi

	# echo "Copying FastCGI programs from `pwd` to $WWW_DIR/api";
	rsync --recursive --delete *.fcgi $WWW_DIR/api/

fi
