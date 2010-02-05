#!/bin/bash

. ../../config.sh

if ../../tools/iamservertype -q cgi; then
	if [ ! -d $WWW_DIR/auth/ ]; then
		mkdir $WWW_DIR/auth;
	fi

	echo "Copying FastCGI programs to $WWW_DIR/auth/";
	rsync --recursive --delete *.fcgi $WWW_DIR/auth/
fi
