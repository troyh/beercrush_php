. ../../config.sh;

if [ -z "$LOCALDATA_DIR" ]; then
	echo "ERROR: LOCALDATA_DIR not specified.";
	exit 1;
fi

if [ ! -d $LOCALDATA_DIR/meta/ ]; then
	mkdir $LOCALDATA_DIR/meta;
fi

if [ ! -f $LOCALDATA_DIR/meta/latlonpairs.txt -o ! -f $LOCALDATA_DIR/meta/nearby_beer.txt ]; then
	$BEERCRUSH_BIN_DIR/update_location_data -C /etc/BeerCrush/webapp.conf;
fi
