#!/bin/sh

# Verify that Spread daemon is running
PID_FILE="/var/run/spread/spread.pid";
if [ -f $PID_FILE ]; then
	PID=`cat $PID_FILE`;
	CMD=`ps h -o args  $PID`;
fi

if [ "$CMD" != "/usr/local/sbin/spread -c /etc/spread.conf" ]; then
	echo "ERROR: Spread daemon is not running";
	exit;
fi
