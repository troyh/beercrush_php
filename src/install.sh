#!/bin/bash

. ../config.sh

if ../tools/iamservertype -q cgi; then

	# Try 3 times to stop supervisord
	for N in 1 2 3; do
		if [ "$(/etc/init.d/supervisor status)" = " is running" ]; then 
			echo "Supervisord is running. Attempt #$N to stop it..."; 
			sudo /etc/init.d/supervisor stop;
			sleep 5;
		else
			echo "Supervisord stopped.";
			break;
		fi
	done

	if [ ! -d $WWW_DIR/api ]; then
		mkdir $WWW_DIR/api;
	fi

	# echo "Copying FastCGI programs from `pwd` to $WWW_DIR/api";
	rsync --recursive --delete *.fcgi $WWW_DIR/api/

	sudo /etc/init.d/supervisor start;
	# Try 3 times to start supervisord
	for N in 1 2 3; do
		if [ "$(/etc/init.d/supervisor status)" != " is running" ]; then 
			sudo /etc/init.d/supervisor start;
			sleep 2;
		else
			echo "Supervisord started.";
			break;
		fi
	done

fi
