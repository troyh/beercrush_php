#!/bin/bash

TARGET=${1:-build};
TOPDIR=$(dirname $(readlink -f $0));
RELDIR=$(echo $(pwd)|sed -e "s:^$TOPDIR::"|sed -e 's:^/::');

make --silent $TARGET

for MAKEFILE in $(find . -mindepth 2 -name Makefile -type f |sort| grep -v -e '/\.' | sed -e 's/^\.\///'); do

	# echo $MAKEFILE;
	
	# Special-case src/3rdparty and don't do a make in there
	P=$RELDIR/$MAKEFILE;
	P2=${P#/};
	if [[ $P2 =~ ^src/3rdparty/ ]]; then
		continue;
	fi

	echo "Make $TARGET ($MAKEFILE)";
	make --silent -C $(dirname $MAKEFILE) $TARGET;

done
