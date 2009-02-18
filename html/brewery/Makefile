XSL_DIR=$(WWWDOCROOT)/xsl
XML_DIR=/home/troy/beerliberation/xml

vpath %.xml $(XML_DIR)/brewery
vpath %.xsl $(XSL_DIR)

.PHONY: FORCE

index.html: index.xml $(XSL_DIR)/brewery/index.xsl
	xmlstarlet tr $(XSL_DIR)/brewery/index.xsl $(XML_DIR)/brewery/index.xml > $(WWWDOCROOT)$(WWWRELPATH)$@

index.xml: FORCE
	echo "<breweries>" > $(XML_DIR)/brewery/$@
	echo "<meta>" >> $(XML_DIR)/brewery/$@
	echo "<breadcrumbs><crumb href=\"/brewery/\">Breweries</crumb></breadcrumbs>" >> $(XML_DIR)/brewery/$@
	echo "</meta>" >> $(XML_DIR)/brewery/$@
	find $(XML_DIR)/brewery -maxdepth 1 -name "*.xml" ! -name ".*" ! -name "index.xml" -exec xmlstarlet fo --omit-decl {} \; >> $(XML_DIR)/brewery/$@
	echo "</breweries>" >> $(XML_DIR)/brewery/$@

%.html: %.xml brewery.xsl $(WWWDOCROOT)/$(WWWRELPATH)/
	xmlstarlet tr $(XSL_DIR)/brewery.xsl $< > $(WWWDOCROOT)$(WWWRELPATH)$@

$(WWWDOCROOT)$(WWWRELPATH):
	mkdir -p $(WWWDOCROOT)$(WWWRELPATH)
	