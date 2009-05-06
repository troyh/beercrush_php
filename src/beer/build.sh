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

SYSLOG_INFO "$FILENAME changed";

OBJ_DIR=/tmp

export WWW_DIR
export APP_DIR
export OBJ_DIR

make -C /home/troy/oak 2> /tmp/build.sh.out
if [ $? != 0 ]; then
	SYSLOG_ERROR "OAK Make failed:";
	SYSLOG_INFO < /tmp/build.sh.out;
fi


case "$FILENAME" in
	review.cc )
		if [ ! -d $WWW_DIR/api/beer ]; then  mkdir -p $WWW_DIR/api/beer; fi;
		if [ ! -d $OBJ_DIR/src/beer ]; then  mkdir -p $OBJ_DIR/src/beer; fi;
		SYSLOG_INFO "make -C `dirname $0` $WWW_DIR/api/beer/$BASENAME.fcgi"
		make -C `dirname $0` $WWW_DIR/api/beer/$BASENAME.fcgi 2> /tmp/build.sh.out
		if [ $? != 0 ]; then
			SYSLOG_ERROR "Make failed:";
		fi
		SYSLOG_INFO < /tmp/build.sh.out;
		;;
esac


# g++ $INCLUDES -c -o $OBJ_DIR/$BASENAME.o $DIR/$FILENAME 2> /tmp/g++-$FILENAME.out;
# if [ $? != 0 ]; then
# 	SYSLOG_ERROR "Compilation failed (g++ $INCLUDES -c -o $OBJ_DIR/$BASENAME.o $DIR/$FILENAME):";
# 	SYSLOG_INFO < /tmp/g++-$FILENAME.out;
# else
# 	g++ $INCLUDES -c -o $OBJ_DIR/BeerCrush_types.o $DIR/../gen-cpp/BeerCrush_types.cpp 2> /tmp/g++-BeerCrush_types.out;
# 	if [ $? != 0 ]; then
# 		SYSLOG_ERROR "Compilation failed (g++ $INCLUDES -c -o $OBJ_DIR/BeerCrush_types.o $DIR/gen-cpp/BeerCrush_types.cpp):";
# 		SYSLOG_INFO < /tmp/g++-BeerCrush_types.out;
# 	else
# 		g++ $INCLUDES -c -o $OBJ_DIR/BeerCrush_constants.o $DIR/../gen-cpp/BeerCrush_constants.cpp 2> /tmp/g++-BeerCrush_constants.out;
# 		if [ $? != 0 ]; then
# 			SYSLOG_ERROR "Compilation failed (g++ $INCLUDES -c -o $OBJ_DIR/BeerCrush_constants.o $DIR/gen-cpp/BeerCrush_constants.cpp):";
# 			SYSLOG_INFO < /tmp/g++-BeerCrush_constants.out;
# 		else
# 			g++ $OBJ_DIR/$BASENAME.o $OBJ_DIR/BeerCrush_constants.o $OBJ_DIR/BeerCrush_types.o -lfcgi -lcgic_fcgi -lxml2 -lxslt -lmemcached -lgcrypt -loak -lboost_filesystem -L/home/troy/oak -lthrudoc -lthrucommon -lspread -o $WWW_DIR/api/beer/$BASENAME.fcgi 2> /tmp/g++-$FILENAME.out;
# 			if [ $? != 0 ]; then
# 				SYSLOG_ERROR "Link failed (g++ $OBJ_DIR/$BASENAME.o -lfcgi -lcgic_fcgi -lxml2 -lxslt -lmemcached -lgcrypt -loak -lboost_filesystem -L/home/troy/oak -lthrudoc -lthrucommon -lspread -o $WWW_DIR/api/beer/$BASENAME.fcgi):";
# 				SYSLOG_INFO < /tmp/g++-$FILENAME.out;
# 			else
# 				SYSLOG_INFO "Rebuilt $WWW_DIR/api/beer/$BASENAME.fcgi (after change to $FILENAME)";
# 			fi
# 		fi
# 	fi
# fi
