#!/bin/bash

. /etc/BeerCrush/BeerCrush.conf

XML_DIR=$DOC_DIR
XSL_DIR=$APP_DIR/xsl

PWD=`pwd`;
WWWRELPATH=`echo $PWD|sed -e "s|^$APP_DIR||"`;

if [ "$1" == "html" ]; then
	
	xmlstarlet tr $XSL_DIR/brewery/index.xsl $XML_DIR/brewery/index.xml > $WWW_DIR/$WWWRELPATH/index.html
	
elif [ "$1" == "xml" ]; then
	
	cat > index.xml <<EOF
<breweries>
	<meta>
		<breadcrumbs><crumb href="/brewery/">Breweries</crumb></breadcrumbs>
	</meta>
	`find $XML_DIR/brewery -maxdepth 1 -name "*.xml" ! -name ".*" ! -name "index.xml" -exec xmlstarlet fo --omit-decl {} \;`
</breweries>
EOF

fi
