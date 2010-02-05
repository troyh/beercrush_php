#!/bin/bash

WWW_DIR=$(php -r '$$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $$cfg->file_locations->WWW_DIR."\n";');

if [ -z "$WWW_DIR" ]; then
	echo "WWW_DIR is empty!"
	exit;
fi
