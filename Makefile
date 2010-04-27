include Makefile.rules

ALL: SAFETY_CHECK /usr/local/include/jansson.h /usr/include/curl/curl.h /usr/bin/yui-compressor	/etc/supervisor/supervisord.conf

/usr/local/include/jansson.h: 
	echo "Jansson must be built and installed";
	exit 1;	

/usr/include/curl/curl.h:
	sudo apt-get install libcurl4-openssl-dev

/usr/bin/yui-compressor:
	sudo apt-get install yui-compressor

/etc/supervisor/supervisord.conf:
	sudo apt-get install supervisor

