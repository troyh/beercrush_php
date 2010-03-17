#!/bin/sh

# Verify that Spread daemon is running
PID=`cat /var/run/spread/spread.pid`;
CMD=`ps h -o args  $PID`;

if [ "$CMD" != "/usr/local/sbin/spread -c /etc/spread.conf" ]; then
	echo "Spread daemon is not running";
	exit;
fi
