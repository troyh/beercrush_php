#!/bin/sh

# Verify that PHP's magic quotes are off (magic_quotes_gpc=Off in /etc/php5/cgi/php.ini 
# and /etc/php5/cli/php.ini)

if grep -i  "magic_quotes_gpc\s*=\s*on"  /etc/php5/cgi/php.ini /etc/php5/cli/php.ini; then 
	cat - <<EOF
************************************************************
PHP magic quotes must be turned off in both 
/etc/php5/cgi/php.ini and /etc/php5/cli/php.ini:

	magic_quotes_gpc=Off
************************************************************
EOF
	exit 1;
fi

# TODO: make sure PHP's include_path is correct and uncommented in php.ini
