include ../../Makefile.rules

ALL:

install: SAFETY_CHECK
	if [ ! -d /etc/BeerCrush ]; then \
		sudo mkdir /etc/BeerCrush; \
		sudo chown www-data.www-data /etc/BeerCrush; \
		sudo chmod ug+rwX /etc/BeerCrush; \
	fi
	sh install.sh
	$(RECURSIVE_MAKE);

check:
	sh ./check.sh
	$(RECURSIVE_MAKE);
