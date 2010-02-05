#!/bin/bash

if ../tools/iamservertype -q web; then
	echo "I'm a web server!";
	if [ ! -d $(WWW_DIR)/js ]; then
		echo "Making $(WWW_DIR)/js";
		mkdir $(WWW_DIR)/js;
	fi
	rsync --recursive --delete *.js $(WWW_DIR)/js/;
fi
