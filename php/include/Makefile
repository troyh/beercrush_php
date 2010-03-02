include ../../Makefile.rules

install: SAFETY_CHECK
	if [ ! -d /usr/share/php/beercrush/ ]; then \
		sudo mkdir /usr/share/php/beercrush/;\
		sudo chown www-data.www-data /usr/share/php/beercrush/;\
		sudo chmod ug+rw /usr/share/php/beercrush/;\
	fi
	rsync --recursive --delete --exclude=".*" --exclude="OAK/" --include="*/" --include="*.php" --exclude="*"  ./ /usr/share/php/beercrush/
	$(RECURSIVE_MAKE)
