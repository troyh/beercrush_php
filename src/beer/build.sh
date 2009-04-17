#!/bin/bash

# --START INCRONTAB RULES--
#
# $APP_DIR/src/beer/review.cc IN_MODIFY
#
# --END INCRONTAB RULES--

. /etc/BeerCrush/BeerCrush.conf

. $APP_DIR/etc/utils.sh

DIR=$(dirname $1);
FILENAME=$(basename $1);
EXT=$(FILEEXT $1);
BASENAME=$(basename $1 .$EXT);

OBJ_DIR=/tmp
INCLUDES="-I/usr/include/boost -I/usr/include/libxml2 -I/home/troy/oak/"

SYSLOG_INFO "$FILENAME changed";

case "$FILENAME" in
	review.cc )
		if [ ! -d $WWW_DIR/api/beer ]; then  mkdir $WWW_DIR/api/beer; fi;
		g++ $INCLUDES -c -o $OBJ_DIR/$BASENAME.o $DIR/$FILENAME 2> /tmp/g++-$FILENAME.out;
		if [ $? != 0 ]; then
			SYSLOG_ERROR "Compilation failed (g++ -I$INCLUDES -c -o $OBJ_DIR/$BASENAME.o $DIR/$FILENAME):";
			SYSLOG_INFO < /tmp/g++-$FILENAME.out;
		else
			g++ $OBJ_DIR/$BASENAME.o -lfcgi -lcgic_fcgi -lxml2 -lxslt -lmemcached -lgcrypt -loak -lboost_filesystem -L/home/troy/oak -o $WWW_DIR/api/beer/$BASENAME.fcgi 2> /tmp/g++-$FILENAME.out;
			if [ $? != 0 ]; then
				SYSLOG_ERROR "Link failed (g++ $OBJ_DIR/$BASENAME.o -lfcgi -lcgic_fcgi -lxml2 -o $WWW_DIR/api/beer/$BASENAME.fcgi):";
				SYSLOG_INFO < /tmp/g++-$FILENAME.out;
			else
				SYSLOG_INFO "Rebuilt $WWW_DIR/api/beer/$BASENAME.fcgi (after change to $FILENAME)";
			fi
		fi
		;;
esac
