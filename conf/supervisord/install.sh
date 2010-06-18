#!/bin/bash

. ../../config.sh

PATH=$PATH:$BEERCRUSH_BIN_DIR

if iamservertype -q cgi || iamservertype -q php-cgi; then
	
	sudo cp appserver.conf /etc/supervisor/conf.d/;
	
fi

# Copy all .conf files for daemons that will run on this host
for D in $($BEERCRUSH_BIN_DIR/iamdaemon -t); do
	if [ -f $D.conf ]; then
		sudo cp $D.conf /etc/supervisor/conf.d/;
	fi
done

