#!/bin/sh
. /etc/BeerCrush/BeerCrush.conf

XML_DIR=$DOC_DIR

function make_brewery_meta () {

	BREWERY_ID=`basename $1 .xml`;
	META_FILE=$XML_DIR/meta/brewery/$BREWERY_ID.xml

	# If the file doesn't exist, create a shell doc
	if [ ! -s $META_FILE ]; then
		echo "<brewery id=\"$BREWERY_ID\"/>" | xmlstarlet fo --encode utf-8 - > $META_FILE;
	fi

	# Clear out the /brewery/beerlist element (also creates one if there wasn't a beerlist element to begin with)
	xmlstarlet ed --delete "/brewery/beerlist" --subnode "/brewery" --type elem -n "beerlist" -v "" "$META_FILE" > "$META_FILE.new"

	LOOP=1

	if [ -d $XML_DIR/beer/$BREWERY_ID/ ]; then
		ls $XML_DIR/beer/$BREWERY_ID/ |
		while read F; do
			LN=`xmlstarlet sel -t -m "/beer" -v "@id" -o "&#09;" -v "name" $XML_DIR/beer/$BREWERY_ID/$F`;
			read BEER_ID BEER_NAME <<<$LN;
			# echo "Adding $BEER_NAME ($BEER_ID) LOOP=$LOOP"
			cat "$META_FILE.new" | 
				xmlstarlet ed --subnode "/brewery/beerlist" -t elem -n "beer" -v "$BEER_NAME" |
				xmlstarlet ed --subnode "/brewery/beerlist/beer[$LOOP]" -t attr -n "id" -v "$BEER_ID" |
				xmlstarlet ed --subnode "/brewery/beerlist/beer[$LOOP]" -t attr -n "bjcp_style_id" -v "$BEER_ID" >> "$META_FILE.new2";
				
			mv "$META_FILE.new2" "$META_FILE.new";
			LOOP=$((LOOP + 1));
		done
	fi

	if [ -s "$META_FILE.new" ]; then
		mv "$META_FILE.new" "$META_FILE";
	else
		rm "$META_FILE.new";
	fi

}

if [ ! -d $XML_DIR/meta/brewery ]; then
	mkdir -p $XML_DIR/meta/brewery;
fi


if [ -z "$1" ]; then
	
	ls $DOC_DIR/brewery/ |
	while read B; do
		echo $B
		make_brewery_meta $B
	done

else
	make_brewery_meta $1
fi


