include /etc/BeerCrush/BeerCrush.conf

XSL_DIR=$(APP_DIR)/xsl
XML_DIR=$(DOC_DIR)

vpath %.xml $(XML_DIR)/meta/brewery
vpath %.xsl $(XSL_DIR)

.PHONY: FORCE ALL-BREWERY-METADATA

#
# Make Brewery meta data
#
%.xml: FORCE beerlist.sh
	./beerlist.sh $(basename $(@F))
	
ALL-BREWERY-METADATA: FORCE
	./beerlist.sh
