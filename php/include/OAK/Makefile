include ../../../Makefile.rules

install: SAFETY_CHECK
	if [ ! -d /usr/share/php/OAK/ ]; then \
		sudo mkdir /usr/share/php/OAK/;\
		sudo chown www-data.www-data /usr/share/php/OAK/;\
		sudo chmod ug+rw /usr/share/php/OAK/;\
	fi
	rsync --recursive --delete *.php /usr/share/php/OAK/
	$(RECURSIVE_MAKE)
