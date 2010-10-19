#!/bin/bash

. ../../config.sh;

localdir=$(cat $beercrush_conf_file | ../../tools/jsonpath -1 file_locations.LOCAL_DIR);
gitdir=$localdir/git;

if iamdaemon dbchanges2git; then


	status=$(dpkg --status git | grep "Status:");
	if [ "$status" != "Status: install ok installed" ]; then
		cat - <<EOF
ERROR: git is not installed. Install it:

	sudo apt-get install git

EOF
		exit 1;
	fi

	if [ ! -d $gitdir ]; then
		sudo mkdir -p $gitdir;
		sudo chgrp -R www-data $gitdir;
		sudo chmod -R g+rwx $gitdir;
	fi

	# Init the git repository, if it doesn't exist yet
	if ! git --work-tree=$gitdir --git-dir=$gitdir/.git status > /dev/null; then
		cat - <<EOF
$gitdir is not a git working working directory. You need to create it, do
a db_dump from the BeerCrush database into it. Or just clone it from another
git repo.

EOF
	fi

fi

