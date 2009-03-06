XSL_DIR=/home/troy/beerliberation/app/xsl
XML_DIR=/home/troy/beerliberation/xml

vpath %.xml $(XML_DIR)/meta/brewery
vpath %.xsl $(XSL_DIR)

.PHONY: FORCE ALL-BREWERY-METADATA

#
# Make Brewery meta data
#
%.xml: FORCE beerlist.sh
	./beerlist.sh $(basename $(@F))
	
ALL-BREWERY-METADATA: FORCE
	ls $(XML_DIR)/brewery/ |\
	while read B; do \
		$(MAKE) $$B; \
	done
