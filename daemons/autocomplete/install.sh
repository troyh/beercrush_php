#!/bin/bash

. ../../config.sh

d=$(pwd);
daemonname=${d##*/};

declare -a addr=( $(my_ip_addresses) );
# Take the 1st one
my_ip=${addr[0]};

if iamdaemon $daemonname; then
	
	sed -e "s/_HOST_/$my_ip/" supervisord.conf > /tmp/$daemonname.conf;
	if ! files_are_identical /tmp/$daemonname.conf /etc/supervisor/conf.d/$daemonname.conf; then
		sudo mv /tmp/$daemonname.conf /etc/supervisor/conf.d/$daemonname.conf;
		start_or_restart_service supervisor;
	fi
	
fi

