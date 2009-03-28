#!/bin/bash

. /etc/BeerCrush/BeerCrush.conf

XML_DIR=$DOC_DIR;
XSL_DIR=$APP_DIR/xsl;


if [ -z "$WWWRELPATH" ]; then
	PWD=`pwd`;
	WWWRELPATH=`echo $PWD|sed -e "s|^$APP_DIR||"`;
fi

function make_brewery_page () {
	xmlstarlet tr $XSL_DIR/brewery/brewery.xsl -s XML_DIR=$XML_DIR $XML_DIR/brewery/$1.xml > $DOC_DIR/$WWWRELPATH/$1.html
}

if [ ! -d $DOC_DIR/$WWWRELPATH ]; then
	mkdir -p $DOC_DIR/$WWWRELPATH;
fi

if [ ! -d $WWW_DIR/$WWWRELPATH/byletter ]; then
	mkdir -p $WWW_DIR/$WWWRELPATH/byletter;
fi


if [ -z "$1" ]; then
	
	find $DOC_DIR/brewery/ -maxdepth 1 -name "*.xml" | 
	while read F; do 
		ID=`basename $F .xml`; 
		echo $ID; 
		make_brewery_page $ID
	done

	# Make the "by letter" pages
	xmlstarlet tr $XSL_DIR/brewery/byletter_123.xsl $XML_DIR/brewery/index.xml > $WWW_DIR/$WWWRELPATH/byletter/123.html
	for L in A B C D E F G H I J K L M N O P Q R S T U V W X Y Z; do
		xmlstarlet tr $XSL_DIR/brewery/byletter.xsl -s NAVLETTER=$L $XML_DIR/brewery/index.xml > $WWW_DIR/$WWWRELPATH/byletter/$L.html
	done

else
	BREWERY_ID=`basename $1 .html`;
	make_brewery_page $BREWERY_ID
fi
