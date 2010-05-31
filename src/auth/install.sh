#!/bin/bash

. ../../config.sh

if ../../tools/iamservertype -q cgi; then
	if [ ! -d $WWW_DIR/auth/ ]; then
		mkdir $WWW_DIR/auth;
	fi

	if ! files_are_identical auth.fcgi $WWW_DIR/auth/api.fcgi; then
		echo "Installing auth/api.fcgi";
		sudo supervisorctl stop authapi:*;
		cp api.fcgi $WWW_DIR/auth/;
		sudo supervisorctl start authapi:*;
	fi
fi
