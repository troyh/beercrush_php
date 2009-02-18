XSL_DIR=$(WWWDOCROOT)/xsl
XML_DIR=/home/troy/beerliberation/xml

vpath %.xml $(XML_DIR)/beer
vpath %.xsl $(XSL_DIR)/beer

.PHONY: FORCE

index.html: index.xml index.xsl
	xmlstarlet tr $(XSL_DIR)/beer/index.xsl $(XML_DIR)/beer/index.xml > $(WWWDOCROOT)$(WWWRELPATH)$@

index.xml: FORCE $(WWWDOCROOT)/$(WWWRELPATH)/
	echo "<beers>" > $(XML_DIR)/beer/$@
	echo "<meta>" >> $(XML_DIR)/beer/$@
	echo "<breadcrumbs><crumb href=\"/beer/\">Beers</crumb></breadcrumbs>" >> $(XML_DIR)/beer/$@
	echo "</meta>" >> $(XML_DIR)/beer/$@
	find $(XML_DIR)/beer -maxdepth 1 -name "*.xml" ! -name ".*" ! -name "index.xml" -exec xmlstarlet fo --omit-decl {} \; >> $(XML_DIR)/beer/$@
	echo "</beers>" >> $(XML_DIR)/beer/$@

%.html: %.xml beer.xsl $(WWWDOCROOT)/$(WWWRELPATH)/
	xmlstarlet tr $(XSL_DIR)/beer/beer.xsl -s XML_DIR=$(XML_DIR) $< > $(WWWDOCROOT)$(WWWRELPATH)$@

$(WWWDOCROOT)$(WWWRELPATH):
	mkdir -p $(WWWDOCROOT)$(WWWRELPATH)
