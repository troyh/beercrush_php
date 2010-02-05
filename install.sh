#!/bin/bash

. ./config.sh;

if [ `tools/iamservertype -q php-cgi` -o `tools/iamservertype -q web` ]; then

	if [ ! -d $WWW_DIR ]; then
		echo "Creating $WWW_DIR";
		sudo mkdir $WWW_DIR;
	fi
	
	if [ "$(ls -ld $WWW_DIR | awk '{print $3" "$4}')" != "www-data www-data" ]; then
		echo "Setting permissions on $WWW_DIR";
		sudo chown www-data.www-data $WWW_DIR;
		sudo chmod g+w $WWW_DIR;
	fi

	if tools/iamservertype -q php-cgi; then
		if [ ! -d $WWW_DIR/uploads ]; then
			mkdir $WWW_DIR/uploads;
		fi
	fi
	
fi
