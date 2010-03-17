include ../../Makefile.rules

ALL: SAFETY_CHECK
	@for DIR in $(shell find . -maxdepth 1 -mindepth 1 -type d); do \
		if [ -f $$DIR/Makefile ]; then \
			echo Making $$DIR; \
			make -C $$DIR --silent; \
		fi \
	done

install: SAFETY_CHECK /etc/default/spread /etc/init.d/spread /etc/spread.conf
	sh ./install.sh
	$(RECURSIVE_MAKE);

check: SAFETY_CHECK
	sh ./check.sh
	$(RECURSIVE_MAKE);

/etc/default/spread: default
	sudo cp default $@

/etc/init.d/spread: /etc/rc2.d/S17spread
	sudo ln -f -s $^ $@

/etc/rc2.d/S17spread: init-script
	sudo cp $^ $@

/etc/spread.conf: spread.conf
	sudo cp $^ $@
