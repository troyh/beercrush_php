.PHONY: ALL install

SHELL = /bin/sh

WWW_DIR:=$(shell php -r '$$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $$cfg->file_locations->WWW_DIR."\n";')

ALL:
	@for DIR in $(shell find . -maxdepth 1 -mindepth 1 -type d); do \
		if [ -f $$DIR/Makefile ]; then \
			echo Making $$DIR; \
			make -C $$DIR --silent; \
		fi \
	done

install: 
	@if [ $(shell whoami) != "root" ]; then echo "Must be root to install"; exit 1; fi
	@for DIR in $(shell find . -maxdepth 1 -mindepth 1 -type d); do \
		if [ -f $$DIR/Makefile ]; then \
			echo Installing $$DIR; \
			make -C $$DIR --silent install; \
		fi \
	done; \
    for DIR in api auth css img js html php; do \
		rsync --recursive --delete $$DIR/ $$WWW_DIR/$$DIR/; \
    done
