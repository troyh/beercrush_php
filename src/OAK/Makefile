include ../../Makefile.rules

ALL:

install: SAFETY_CHECK
	if [ ! -d /usr/local/beercrush ]; then \
		sudo mkdir /usr/local/beercrush; \
	fi
	$(RECURSIVE_MAKE);
