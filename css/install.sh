#!/bin/bash

if ../tools/iamservertype -q web; then
	if [ ! -d $(WWW_DIR)/css ]; then
		echo "Making $(WWW_DIR)/css";
		mkdir $(WWW_DIR)/css;
	fi
	
	rsync --recursive --delete *.css $(WWW_DIR)/css/;
fi

