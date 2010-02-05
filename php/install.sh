#!/bin/bash

. ../config.sh

if ../tools/iamservertype -q php-cgi; then

	# Note: the order of --exclude & --include matters here... (we only want non-hidden .php files)
	rsync --recursive --delete --times --exclude=".*" --include="*/" --include="*.php" --exclude="*" ./ $WWW_DIR/php/;

	R=`svnversion -n`;
	for F in index.php footer.php; do
		sed -e "s/<\\!--\\s*SVNVERSION\\s*-->/$R/" $F > $WWW_DIR/php/$F;
	done

fi
