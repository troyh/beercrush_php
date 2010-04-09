include ../../Makefile.rules

ALL:

install: SAFETY_CHECK
	sh ./install.sh;
	$(RECURSIVE_MAKE);

check: 
	sh ./check.sh;
	$(RECURSIVE_MAKE);

setup:
	sudo apt-get install memcached
	$(RECURSIVE_MAKE);
