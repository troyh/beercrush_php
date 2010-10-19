#!/bin/bash

. ../../config.sh

if iamdaemon memcached; then

	# Verify that memcached is installed
	status=$(dpkg --status memcached | grep "Status:");
	if [ "$status" != "Status: install ok installed" ]; then
		cat - <<EOF
		ERROR: Memcached is not installed. Install it:

		sudo apt-get install memcached

EOF
		exit 1;
	fi

fi

