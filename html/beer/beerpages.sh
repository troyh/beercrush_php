#!/bin/bash

. /etc/BeerCrush/BeerCrush.conf

XML_DIR=$DOC_DIR;
XSL_DIR=$APP_DIR/xsl;

if [ -z "$WWWDOCROOT" ]; then
	WWWDOCROOT=$WWW_DIR;
	PWD=`pwd`;
	WWWRELPATH=`echo $PWD|sed -e "s|^$APP_DIR||"`;
fi

find $XML_DIR/brewery/ -maxdepth 1 -name "*.xml" | 
while read B; do 
	BREWERY_ID=`basename $B .xml`; 
	if [ ! -d $WWWDOCROOT/$WWWRELPATH/$BREWERY_ID ]; then 
		echo "Making directory $WWWDOCROOT/$WWWRELPATH/$BREWERY_ID"; 
		mkdir -p $WWWDOCROOT/$WWWRELPATH/$BREWERY_ID; 
	fi; 
	xmlstarlet tr $XSL_DIR/brewery/brewery.xsl -s XML_DIR=$XML_DIR $XML_DIR/brewery/$BREWERY_ID.xml > $WWWDOCROOT/$WWWRELPATH/$BREWERY_ID/index.html; 
	if [ -d $XML_DIR/beer/$BREWERY_ID/ ]; then 
		find $XML_DIR/beer/$BREWERY_ID/ -name "*.xml" | 
		while read F; do 
			BEER_ID=`basename $F .xml`; 
			echo "Making $WWWDOCROOT/$WWWRELPATH/$BREWERY_ID/$BEER_ID.hml"; 
			xmlstarlet tr $XSL_DIR/beer/beer.xsl -s XML_DIR=$XML_DIR $XML_DIR/beer/$BREWERY_ID/$BEER_ID.xml > $WWWDOCROOT/$WWWRELPATH/$BREWERY_ID/$BEER_ID.html; 
		done 
	fi; 
done
