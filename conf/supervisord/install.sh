#!/bin/bash

. ../../config.sh

if ../../tools/iamservertype -q mgmt; then

	sudo cp watch_changes.conf /etc/supervisor/conf.d/;

fi

if ../../tools/iamservertype -q cgi || ../../tools/iamservertype -q php-cgi; then
	
	sudo cp appserver.conf /etc/supervisor/conf.d/;
	
fi

