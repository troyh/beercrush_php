<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />

	<xsl:template match="/post">
		<xsl:apply-templates select="doc"/>
	</xsl:template>
	
	<xsl:template match="new_beer">

		<xsl:element name='beer'>
			<xsl:attribute name='id'><xsl:value-of select="$ID" /></xsl:attribute>
			<xsl:attribute name='bjcp_style_id'><xsl:value-of select="bjcp_style_id" /></xsl:attribute>
			<xsl:attribute name='brewery_id'><xsl:value-of select="brewery_id" /></xsl:attribute>
			<xsl:element name='name'><xsl:value-of select="name" /></xsl:element>
			<xsl:element name='description'><xsl:value-of select="description" /></xsl:element>
			<xsl:element name='abv'><xsl:value-of select="abv" /></xsl:element>
		</xsl:element>		

	</xsl:template>
	
</xsl:stylesheet>
