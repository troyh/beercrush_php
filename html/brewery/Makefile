include /etc/BeerCrush/BeerCrush.conf

XSL_DIR=$(APP_DIR)/xsl
XML_DIR=$(DOC_DIR)

vpath %.xml $(XML_DIR)/brewery
vpath %.xsl $(XSL_DIR)

.PHONY: FORCE ALL-BREWERY-PAGES

index.html: index.xml $(XSL_DIR)/brewery/index.xsl
	./makeindex.sh html

index.xml: FORCE
	./makeindex.sh xml

%.html: %.xml makepages.sh brewery.xsl $(WWWDOCROOT)/$(WWWRELPATH)/
	xmlstarlet tr $(XSL_DIR)/brewery/brewery.xsl $< > $(WWWDOCROOT)$(WWWRELPATH)$@

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
	./makepages.sh
	
