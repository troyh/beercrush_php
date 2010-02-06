#!/bin/bash

. ../config.sh;

if ../tools/iamservertype -q web; then
	if [ ! -d $WWW_DIR/img ]; then \
		echo "Making $WWW_DIR/img"; \
		mkdir $WWW_DIR/img; \
	fi

	rsync --recursive --delete *.png *.jpg *.gif $WWW_DIR/img/;
fi
