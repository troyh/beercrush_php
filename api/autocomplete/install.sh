#!/bin/bash

. ../../config.sh

make --silent local_install

if iamdaemon autocomplete; then

	if [ ! -d $WWW_DIR/api ]; then
		mkdir $WWW_DIR/api;
	fi

	if ! files_are_identical autocomplete.fcgi $WWW_DIR/api/autocomplete.fcgi; then
		echo "Installing autocomplete.fcgi";
		sudo supervisorctl stop autocomplete:*;
		cp autocomplete.fcgi $WWW_DIR/api/autocomplete.fcgi;
		sudo supervisorctl start autocomplete:*;
	fi

fi
