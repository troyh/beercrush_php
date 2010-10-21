#!/bin/sh

#
# Make sure that Spread 4.1.0 is installed
#
if ! echo "q" | spuser | head -1 | grep "Spread library version is 4.1.0" > /dev/null; then
	cat - <<EOF
The Spread Toolkit 4.1.0 must be installed. To do that:

	cd src/3rdparty 
	tar xvzf spread-src-4.1.0.tar.gz 
	cd spread-src-4.1.0/
	./configure --sysconfdir=/etc
	make
	sudo make install
	sudo ldconfig
	sudo rm -rf /usr/lib/libspread.* /usr/lib/libtspread.*

EOF
	exit 1;
fi;

#
# Make sure that the PHP Spread extension is installed
#
if php -c /etc/php5/cgi/php.ini -r 'if (function_exists("spread_connect")) exit(1);' || php -c /etc/php5/cli/php.ini -r 'if (function_exists("spread_connect")) exit(1);'; then 
	cat - <<EOF
The Spread PHP extension must be installed. To do that:

	cd src/3rdparty/
	tar xvzf spread-2.1.0.tgz 
	cd spread-2.1.0/
	phpize
	./configure 
	make
	sudo make install

Add extension=spread.so to PHP .ini files:

	/etc/php5/cli/php.ini
	/etc/php5/cgi/php.ini

EOF

	exit 2;

fi

