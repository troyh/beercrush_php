include Makefile.rules

.PHONY: install setup check

ALL: SAFETY_CHECK /usr/local/include/jansson.h /usr/include/curl/curl.h /usr/bin/yui-compressor	/etc/supervisor/supervisord.conf
	@for DIR in $(shell find . -maxdepth 1 -mindepth 1 -type d); do \
		if [ -f $$DIR/Makefile ]; then \
			echo Making $$DIR; \
			make -C $$DIR --silent; \
		fi \
	done

/usr/local/include/jansson.h: 
	echo "Jansson must be built and installed";
	exit 1;	

/usr/include/curl/curl.h:
	sudo apt-get install libcurl4-openssl-dev

/usr/bin/yui-compressor:
	sudo apt-get install yui-compressor

/etc/supervisor/supervisord.conf:
	sudo apt-get install supervisor

install: SAFETY_CHECK ALL
	@sh ./supervisord.sh stop;
	@sh ./install.sh
	$(RECURSIVE_MAKE);
	@./supervisord.sh start;

check:
	@sh ./check.sh
	$(RECURSIVE_MAKE);

setup:
	$(RECURSIVE_MAKE);
