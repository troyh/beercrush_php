XSL_DIR=$(WWWDOCROOT)/xsl
XML_DIR=/home/troy/beerliberation/xml

vpath %.xml $(XML_DIR)/brewery
vpath %.xsl $(XSL_DIR)

.PHONY: FORCE ALL-BREWERY-PAGES

index.html: index.xml $(XSL_DIR)/brewery/index.xsl
	xmlstarlet tr $(XSL_DIR)/brewery/index.xsl $(XML_DIR)/brewery/index.xml > $(WWWDOCROOT)$(WWWRELPATH)$@

index.xml: FORCE
	echo "<breweries>" > $@
	echo "<meta>" >> $@
	echo "<breadcrumbs><crumb href=\"/brewery/\">Breweries</crumb></breadcrumbs>" >> $@
	echo "</meta>" >> $@
	find $(XML_DIR)/brewery -maxdepth 1 -name "*.xml" ! -name ".*" ! -name "index.xml" -exec xmlstarlet fo --omit-decl {} \; >> $@
	echo "</breweries>" >> $@

%.html: %.xml brewery.xsl $(WWWDOCROOT)/$(WWWRELPATH)/
	xmlstarlet tr $(XSL_DIR)/brewery.xsl $< > $(WWWDOCROOT)$(WWWRELPATH)$@

#
# The different XSL for 123 versus A,B,C,.. is required because XSL provides NO MEANS to compare strings. It's shockingly idiotic.
# The people who made XSLT 1.0 should be shot and their families jailed for crimes against programmers.
#
byletter/123.html: $(XML_DIR)/brewery/index.xml $(XSL_DIR)/brewery/byletter_123.xsl $(WWWDOCROOT)/$(WWWRELPATH)/byletter/
	xmlstarlet tr $(XSL_DIR)/brewery/byletter_123.xsl $< > $(WWWDOCROOT)/$(WWWRELPATH)/$@

byletter/%.html: $(XML_DIR)/brewery/index.xml $(XSL_DIR)/brewery/byletter.xsl $(WWWDOCROOT)/$(WWWRELPATH)/byletter/
	xmlstarlet tr $(XSL_DIR)/brewery/byletter.xsl -s NAVLETTER=$(basename $(@F)) $< > $(WWWDOCROOT)/$(WWWRELPATH)/$@

$(WWWDOCROOT)/$(WWWRELPATH)/byletter/:
	mkdir $(WWWDOCROOT)/$(WWWRELPATH)/byletter/;
	
$(WWWDOCROOT)$(WWWRELPATH):
	mkdir -p $(WWWDOCROOT)$(WWWRELPATH)
	
ALL-BREWERY-PAGES: FORCE
	find $(XML_DIR)/brewery/ -maxdepth 1 -name "*.xml" | \
	while read F; do \
		ID=`basename $$F .xml`; \
		echo $$ID; \
		WWWDOCROOT=$(WWWDOCROOT) WWWRELPATH=$(WWWRELPATH) make --quiet $$ID.html; \
	done
	