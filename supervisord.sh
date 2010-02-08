#!/bin/bash

if [ "$1" = "stop" ]; then

	if [ `tools/iamservertype -q php-cgi` -o `tools/iamservertype -q mgmt` ]; then
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
	fi

elif [ "$1" = "start" ]; then
	
	if [ `tools/iamservertype -q php-cgi` -o `tools/iamservertype -q mgmt` ]; then
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

fi
