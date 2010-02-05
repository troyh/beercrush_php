#!/bin/bash

. ../config.sh

if ../tools/iamservertype -q cgi; then

	if /etc/init.d/supervisor status > /dev/null; then sudo /etc/init.d/supervisor stop; fi
	sleep 1;
	if /etc/init.d/supervisor status > /dev/null; then sudo /etc/init.d/supervisor stop; fi
	sleep 1;
	if /etc/init.d/supervisor status > /dev/null; then sudo /etc/init.d/supervisor stop; fi
	sleep 1;

	if [ ! -d $WWW_DIR/api ]; then
		mkdir $WWW_DIR/api;
	fi

	echo "Copying FastCGI programs to $WWW_DIR/api";
	rsync --recursive --delete *.fcgi $WWW_DIR/api/

	sudo /etc/init.d/supervisor start;

fi
