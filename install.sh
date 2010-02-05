#!/bin/bash

. ./config.sh;

if [ ! -d $WWW_DIR ]; then
	mkdir $WWW_DIR;
fi
