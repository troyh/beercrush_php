#!/bin/bash

function FILEEXT () {
	echo $(echo $1 | sed -e 's/^.*\.\([^\.]*\)$/\1/');
}

function LOCK () {
	exec 200>"$1"
	flock -x --timeout 30 200;
}

function SYSLOG_INFO () {
	logger -t BeerCrush -s -p user.info "$1";
}

function SYSLOG_WARN () {
	logger -t BeerCrush -s -p user.warn "$1";
}

function SYSLOG_ERROR () {
	logger -t BeerCrush -s -p user.emerg "$1";
}

function SYSLOG_EMERG () {
	logger -t BeerCrush -s -p user.emerg "$1";
}
