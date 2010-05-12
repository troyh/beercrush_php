#!/bin/bash

. ../../config.sh

# The uncache daemon is installed on every NGiNX server
if iamdaemon nginx; then
	if ! files_are_identical uncache.conf /etc/BeerCrush/daemons/uncache; then
		echo "Installing uncache daemon";
		cp uncache.conf /etc/BeerCrush/daemons/uncache;
	fi
fi
