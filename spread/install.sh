#!/bin/bash

. ../config.sh

PATH=$PATH:$BEERCRUSH_BIN_DIR

for D in solr_indexer; do
	
	if $BEERCRUSH_BIN_DIR/iamdaemon -q $D; then
		sudo cp $D /usr/local/beercrush/bin;
	fi
	
done

