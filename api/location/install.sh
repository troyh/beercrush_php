. ../../config.sh;

if [ ! -f $LOCALDATA_DIR/meta/latlonpairs.txt -o ! -f $LOCALDATA_DIR/meta/nearby_beer.txt ]; then
	$BEERCRUSH_BIN_DIR/update_location_data -C /etc/BeerCrush/webapp.conf;
fi
