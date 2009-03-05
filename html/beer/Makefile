XSL_DIR=$(WWWDOCROOT)/xsl
XML_DIR=/home/troy/beerliberation/xml

vpath %.xml $(XML_DIR)/beer
vpath %.xsl $(XSL_DIR)/beer

.PHONY: FORCE ALL-BEER-PAGES

index.html: index.xml index.xsl
	xmlstarlet tr $(XSL_DIR)/beer/index.xsl $(XML_DIR)/beer/index.xml > $(WWWDOCROOT)$(WWWRELPATH)$@

index.xml: FORCE $(WWWDOCROOT)/$(WWWRELPATH)/
	echo "<beers>" > $(XML_DIR)/beer/$@
	echo "<meta>" >> $(XML_DIR)/beer/$@
	echo "<breadcrumbs><crumb href=\"/beer/\">Beers</crumb></breadcrumbs>" >> $(XML_DIR)/beer/$@
	echo "</meta>" >> $(XML_DIR)/beer/$@
	find $(XML_DIR)/beer -mindepth 2 -name "*.xml" ! -name ".*" ! -name "index.xml" -exec xmlstarlet fo --omit-decl {} \; >> $(XML_DIR)/beer/$@
	echo "</beers>" >> $(XML_DIR)/beer/$@

#
# Makes the brewery page
#
%/index.html: $(XML_DIR)/brewery/$(subst /,.xml,$@) $(XSL_DIR)/brewery/brewery.xsl
	if [ ! -d $(WWWDOCROOT)$(WWWRELPATH)$(@D) ]; then \
		mkdir $(WWWDOCROOT)$(WWWRELPATH)$(@D); \
	fi
	xmlstarlet tr $(XSL_DIR)/brewery/brewery.xsl -s XML_DIR=$(XML_DIR) $(XML_DIR)/brewery/$(@D).xml > $(WWWDOCROOT)$(WWWRELPATH)$@

#
# Makes an individual beer page
#
%: %.xml $(XSL_DIR)/beer/beer.xsl $(WWWDOCROOT)/$(WWWRELPATH)/
	xmlstarlet tr $(XSL_DIR)/beer/beer.xsl -s XML_DIR=$(XML_DIR) $< > $(WWWDOCROOT)/$(WWWRELPATH)/$(if $(suffix $@),$@,$@.html)

#
# Makes the brewery page (by executing make with the /index.html added to the target)
#
%: $(XML_DIR)/brewery/%.xml
	$(MAKE) $@/index.html

$(WWWDOCROOT)/$(WWWRELPATH):
	mkdir -p $(WWWDOCROOT)/$(WWWRELPATH)

ALL-BEER-PAGES: FORCE
	find $(XML_DIR)/brewery/ -maxdepth 1 -name "*.xml" | \
	while read B; do \
		BREWERY_ID=`basename $$B .xml`; \
		if [ ! -d $(WWWDOCROOT)$(WWWRELPATH)$$BREWERY_ID ]; then \
			echo "Making directory $(WWWDOCROOT)$(WWWRELPATH)$$BREWERY_ID"; \
			mkdir $(WWWDOCROOT)$(WWWRELPATH)$$BREWERY_ID; \
		fi; \
		xmlstarlet tr $(XSL_DIR)/brewery/brewery.xsl -s XML_DIR=$(XML_DIR) $(XML_DIR)/brewery/$$BREWERY_ID.xml > $(WWWDOCROOT)$(WWWRELPATH)$$BREWERY_ID/index.html; \
		if [ -d $(XML_DIR)/beer/$$BREWERY_ID/ ]; then \
			find $(XML_DIR)/beer/$$BREWERY_ID/ -name "*.xml" | \
			while read F; do \
				BEER_ID=`basename $$F .xml`; \
				echo "Making $(WWWDOCROOT)$(WWWRELPATH)$$BREWERY_ID/$$BEER_ID.hml"; \
				xmlstarlet tr $(XSL_DIR)/beer/beer.xsl -s XML_DIR=$(XML_DIR) $(XML_DIR)/beer/$$BREWERY_ID/$$BEER_ID.xml > $(WWWDOCROOT)$(WWWRELPATH)$$BREWERY_ID/$$BEER_ID.html; \
			done \
		fi; \
	done


