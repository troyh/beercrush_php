include ../Makefile.rules

ALL:

install: SAFETY_CHECK /etc/BeerCrush/schema.json
	sh ./install.sh
	$(RECURSIVE_MAKE);

/etc/BeerCrush/schema.json: schema.json
	cp schema.json $@
	
check:
	sh ./check.sh
	$(RECURSIVE_MAKE);

setup:
	$(RECURSIVE_MAKE);
