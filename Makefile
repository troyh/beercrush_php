.PHONY: ALL install

SHELL = /bin/sh

ALL:

install: 
	@if [ $(shell whoami) != "root" ]; then echo "Must be root to install"; exit 1; fi
	@for DIR in $(shell find . -maxdepth 1 -mindepth 1 -type d); do \
		if [ -f $$DIR/Makefile ]; then \
			echo Installing $$DIR; \
			make -C $$DIR --silent install; \
		fi \
	done
