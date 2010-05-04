#!/bin/bash

. $(dirname $0)/config.sh

iamcron() {
	if ls $BEERCRUSH_ETC_DIR/cron/ | grep $1 > /dev/null; then
		return 0;
	fi
	return 1;
}

iamdaemon() {
	if ls $BEERCRUSH_ETC_DIR/daemons/ | grep $1 > /dev/null; then
		return 0;
	fi
	return 1;
}

install_file() {
	if [ -d $2 ]; then
		DEST=$2/$1;
	elif [ -f $2 ]; then
		DEST=$2
	elif [ -d $(dirname $2) ]; then
		DEST=$2
	else
		echo "ERROR: $2 is neither a file or a directory. Can't install into it.";
	fi

	if [ ! -f $DEST ]; then
		sudo touch $DEST; # Make zero-length file just so the md5sum doesn't error...
		sudo chown $USER $DEST;
	fi
	
	md5sum $1 $DEST | cut -f 1 -d ' ' | paste - - | if read A B; then
		if [[ $A != $B ]]; then
			if cp $1 $DEST; then
				echo "Installed $DEST";
				return 1;
			else
				echo "ERROR: Unable to install $DEST";
			fi
		fi
	fi

	return 0;
}

install_routine() {
	
	SUBDIR=$1;

	#################################
	# Install files from install.files
	#################################
	if [ -f install.files ]; then
		cat install.files |sed -e '/^ *$/d' | while read LOC FNAME; do
			FNAME=$(eval "echo $FNAME");
			case $LOC in 
				DIR)
					if [ ! -d $FNAME ]; then
						if [ ! -d $FNAME ]; then
							if mkdir $FNAME 2> /dev/null; then
								echo "Made directory $FNAME";
								sudo chgrp $BEERCRUSH_APPSERVER_USER $FNAME;
						        sudo chmod -R ug+rwX $FNAME;
							elif sudo mkdir $FNAME 2> /dev/null; then
								echo "Made directory $FNAME (as sudo)";
								sudo chgrp $BEERCRUSH_APPSERVER_USER $FNAME;
						        sudo chmod -R ug+rwX $FNAME;
							else
								echo "Failed to make directory $FNAME";
							fi
						fi
					fi
					;;
				BIN) 
					if install_file $FNAME $BEERCRUSH_BIN_DIR; then
						if ! chmod +x $BEERCRUSH_BIN_DIR/$FNAME; then
							echo "ERROR: Unable to make $BEERCRUSH_BIN_DIR/$FNAME executable.";
						fi
					fi
					;;
				ETC)
					install_file $FNAME $BEERCRUSH_ETC_DIR;
					;;
				SYMLINK)
					echo $FNAME | if read LINK ORIG; then
						if [ ! -L $LINK ];then
							if sudo ln -s -f $ORIG $LINK; then
								echo "Made symlink $LINK ($ORIG)";
							else
								echo "ERROR: Unable to make symlink $LINK";
							fi
						fi
					fi
					;;
				*)
					if [[ $LOC =~ ^/ ]]; then
						# Explicit path
						install_file "$FNAME" "$LOC";
					else
						echo "ERROR: Invalid install.files location $LOC (must be an absolute path or a predefined identifier)";
					fi
					;;
			esac
		done
	fi

	#################################
	# Install cron jobs
	#################################
	if [ -f *.crontab ]; then
		for CRONTAB in *.crontab; do
			CRON_NAME=$(basename $CRONTAB .crontab);
			if iamcron $CRON_NAME; then
				# crond will not run scripts with a _ or a . in the name! see http://www.debian-administration.org/articles/56
				# So we remove _ or . from the name:
				CROND_FILENAME=$(echo $CRON_NAME|sed -e 's/[_\.]//g');
				
				if [ ! -f /etc/cron.d/$CROND_FILENAME ]; then
					echo "Installing crontab: $CRONTAB";
					sudo cp $CRONTAB /etc/cron.d/$CROND_FILENAME;
				fi
			elif [ -f /etc/cron.d/$CROND_FILENAME ]; then
				echo "Uninstalling crontab: $CRONTAB";
				sudo rm /etc/cron.d/$CROND_FILENAME;
			fi
		done
	fi

	#################################
	# Install Supervisord programs
	#################################
	if [ -f *.supervisord ]; then
		for SUP in *.supervisord; do
			DAEMON_NAME=$(basename $SUP .supervisord);
			if iamdaemon $DAEMON_NAME; then
				if [ ! -f /etc/supervisor/conf.d/$DAEMON_NAME.conf ]; then
					echo "Installing supervisord program: $DAEMON_NAME.conf";
					sudo cp $SUP /etc/supervisor/conf.d/$DAEMON_NAME.conf;
				fi
			elif [ -f /etc/supervisord/conf.d/$DAEMON_NAME.conf ]; then
				echo "Uninstalling supervisord program: $DAEMON_NAME.conf";
				sudo rm /etc/supervisor/conf.d/$DAEMON_NAME.conf;
			fi
		done
	fi

	#################################
	# Run install.sh (if exists)
	#################################
	if [ -f install.sh ]; then
		bash install.sh;
	fi
}

install_routine .;

for SUBDIR in $(find . -type d ! -name '.*' | sort | grep -v -e '/\.' | sed -e 's/^\.\///'); do

	# Special-case src/3rdparty and don't do a make in there
	if [[ $(basename $(pwd))/$SUBDIR =~ ^src/3rdparty/ ]]; then
		continue;
	fi

	ORIGDIR=$(pwd);
	cd $SUBDIR;
	# echo "Running script in $(pwd)";
	install_routine $SUBDIR; # Run through install routine in the subdir
	cd $ORIGDIR;
done

