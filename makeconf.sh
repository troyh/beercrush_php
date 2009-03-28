#!/bin/bash

CONF_FILE=$1;

APP_DIR=`pwd`

DEFAULT_DOC_DIR="/home/troy/beercrush/xml"
DEFAULT_WWW_DIR="/var/www/BeerCrush"


sudo apt-get install xmlstarlet	

read -e -p "Location of documents [$DEFAULT_DOC_DIR]:" DOC_DIR

# Verify that $DOC_DIR exists
if [ ! -d $DOC_DIR ]; then
	echo "$DOC_DIR doesn't exist";
	exit;
fi

if [ -z "$DOC_DIR" ]; then
	DOC_DIR=$DEFAULT_DOC_DIR;
fi


read -e -p "Location of Apache DocumentRoot [$DEFAULT_WWW_DIR]:" WWW_DIR

# Verify that $WWW_DIR exists
if [ ! -d $WWW_DIR ]; then
	echo "$WWW_DIR doesn't exist";
	exit;
fi

if [ -z "$WWW_DIR" ]; then
	WWW_DIR=$DEFAULT_WWW_DIR;
fi



# TODO: make sure that $DOC_DIR looks like the documents directory (i.e., has the expected subdirs)

cat > $CONF_FILE <<EOF
APP_DIR=$APP_DIR
DOC_DIR=$DOC_DIR
WWW_DIR=$WWW_DIR
EOF

# Put the config file in place
sudo sed -e "s|{WWW_DIR}|$WWW_DIR|" -e "s|{APP_DIR}|$APP_DIR|" -e "s|{DOC_DIR}|$DOC_DIR|"  $APP_DIR/etc/httpd.conf > /etc/apache2/sites-available/BeerCrush

# Enable the site
sudo a2ensite BeerCrush

# Make links to appropriate directories
if [ ! -L $WWW_DIR/api ]; then ln -s $APP_DIR/api $WWW_DIR/api; fi
if [ ! -L $WWW_DIR/css ]; then ln -s $APP_DIR/css $WWW_DIR/css; fi
if [ ! -L $WWW_DIR/js  ]; then ln -s $APP_DIR/js  $WWW_DIR/js ; fi

# Make other directories
if [ ! -d $WWW_DIR/html ]; then mkdir $WWW_DIR/html; fi
if [ ! -d $WWW_DIR/img  ]; then mkdir $WWW_DIR/img ; fi
