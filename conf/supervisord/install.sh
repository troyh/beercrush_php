#!/bin/bash

. ../../config.sh

PATH=$PATH:$BEERCRUSH_BIN_DIR

if iamservertype -q cgi || iamservertype -q php-cgi; then
	
	sudo cp appserver.conf /etc/supervisor/conf.d/;
	
fi

if iamservertype -q php-cgi; then

	sudo cp php-cgi.conf /etc/supervisor/conf.d/;
	
fi

if iamservertype -q web; then

	sudo cp web.conf /etc/supervisor/conf.d/;
	
fi

if iamservertype -q couchdb-proxy; then

	sudo cp couchdb-proxy.conf /etc/supervisor/conf.d/;
	
fi

if iamservertype -q gitrepo; then

	cp ../../spread/tools/listen /usr/local/beercrush/bin/listen;
	sudo cp dbchanges2git.conf /etc/supervisor/conf.d/;
	
fi

# Copy all .conf files for daemons that will run on this host
for D in $(iamdaemon -t); do
	if [ ! -f $D.conf ]; then
		echo "ERROR: No such .conf file for $D daemon";
		exit 1;
	fi
	sudo cp $D.conf /etc/supervisor/conf.d/;
done

