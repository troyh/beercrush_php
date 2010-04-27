#!/bin/bash

if [ ! -d /usr/share/php/beercrush/ ]; then
	sudo mkdir /usr/share/php/beercrush/;
	sudo chown www-data.www-data /usr/share/php/beercrush/;
	sudo chmod -R ug+rwX /usr/share/php/beercrush/;
fi

rsync --recursive --delete --exclude=".*" --exclude="OAK/" --include="*/" --include="*.php" --exclude="*"  ./ /usr/share/php/beercrush/
