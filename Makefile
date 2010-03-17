include Makefile.rules

.PHONY: install

ALL: SAFETY_CHECK
	@for DIR in $(shell find . -maxdepth 1 -mindepth 1 -type d); do \
		if [ -f $$DIR/Makefile ]; then \
			echo Making $$DIR; \
			make -C $$DIR --silent; \
		fi \
	done

install: SAFETY_CHECK
	sh ./supervisord.sh stop;
	sh ./install.sh
	$(RECURSIVE_MAKE);
	./supervisord.sh start;

check:
	@sh ./check.sh
	$(RECURSIVE_MAKE);
