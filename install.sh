#!/bin/bash

. ./config.sh;

for DIR in $BEERCRUSH_ETC_DIR /usr/local/beercrush/bin; do 
	mkdir -p $DIR;
done

if [ ! -f $BEERCRUSH_ETC_DIR/webapp.conf ]; then
	echo "$BEERCRUSH_ETC_DIR/webapp.conf doesn't exist. You can get a sample from svn://beercrush/conf/appserver/webapp.conf.";
	exit 1;
fi


if tools/iamservertype -q php-cgi || tools/iamservertype -q web; then

	if [ ! -d $WWW_DIR ]; then
		echo "Creating $WWW_DIR";
		sudo mkdir $WWW_DIR;
	fi
	
	if [ "$(ls -ld $WWW_DIR | awk '{print $3" "$4}')" != "www-data www-data" ]; then
		echo "Setting permissions on $WWW_DIR";
		sudo chown www-data.www-data $WWW_DIR;
		sudo chmod -R g+rwX $WWW_DIR;
	fi

fi

if tools/iamservertype -q php-cgi || tools/iamservertype -q cgi; then

	for DIR in  $LOCALDATA_DIR  /var/local/BeerCrush/meta/; do
		if [ ! -d $DIR ]; then
			mkdir -p $DIR;
		fi
	done

	# Set correct permissions on directories
	for D in meta uploads images; do
		chgrp $BEERCRUSH_APPSERVER_USER /var/local/BeerCrush/$D;
		chmod g+rwX /var/local/BeerCrush/$D;
	done
	
fi

if tools/iamservertype -q mgmt; then

	if [ ! -d /var/run/BeerCrush ]; then
		sudo mkdir /var/run/BeerCrush;
	fi
	
	sudo chown www-data.www-data /var/run/BeerCrush;
	sudo chmod g+w /var/run/BeerCrush;
	
fi

if tools/iamservertype -q gitrepo; then
	if [ ! -d /var/local/BeerCrush/git ]; then
		mkdir -p /var/local/BeerCrush/git;
	fi
	
	sudo chgrp -R www-data /var/local/BeerCrush/git;
	sudo chmod -R g+w /var/local/BeerCrush/git;
	
	GIT_DIR="/var/local/BeerCrush/git";
	
	if [ ! -d $GIT_DIR/.git ]; then
		echo "Creating and initializing Git repository...";
		# Init the repo
		git --work-tree=$GIT_DIR --git-dir=$GIT_DIR/.git init;

		git --work-tree=$GIT_DIR --git-dir=$GIT_DIR/.git log;
		if [ $? -ne 0 ]; then
			echo "Getting all documents into Git working tree...";
			# Get all the db docs into the repo
			./tools/db_dump  -C /etc/BeerCrush/webapp.conf -d $GIT_DIR -s
			# Git-add them all and do the initial commit
			echo "Adding all docs...";
			git --work-tree=$GIT_DIR --git-dir=$GIT_DIR/.git add $GIT_DIR;
			echo "commiting...";
			git --work-tree=$GIT_DIR --git-dir=$GIT_DIR/.git commit $GIT_DIR -m 'Initial commit from database dump';

			echo "Made git baseline.";
		fi
	fi
	
fi

# TODO: make autocompletenames.txt and latlonpairs.txt in /var/local/BeerCrush/meta
