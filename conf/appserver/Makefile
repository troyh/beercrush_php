include ../../Makefile.rules

ALL:

install: SAFETY_CHECK
	if [ ! -d /etc/BeerCrush ]; then \
		sudo mkdir /etc/BeerCrush; \
		sudo chown www-data.www-data /etc/BeerCrush; \
		sudo chmod ug+rw /etc/BeerCrush; \
	fi
	$(RECURSIVE_MAKE);

check:
	./check.sh
	$(RECURSIVE_MAKE);
