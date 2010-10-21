#!/bin/bash

. ../config.sh;

if iamdaemon web; then
	if [ ! -d $WWW_DIR/img ]; then \
		echo "Making $WWW_DIR/img"; \
		mkdir $WWW_DIR/img; \
	fi

	rsync --recursive --delete --times --exclude=".*" --include="*/" --include="*.png" --include="*.jpg" --include="*.gif" --exclude="*" ./ $WWW_DIR/img/;

	rsync favicon.ico $WWW_DIR/;
fi
