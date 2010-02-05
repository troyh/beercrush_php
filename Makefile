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
	./install.sh
	$(RECURSIVE_MAKE);
