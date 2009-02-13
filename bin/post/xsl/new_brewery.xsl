<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />

	<xsl:template match="/post">
		<xsl:apply-templates select="doc"/>
	</xsl:template>
	
	<xsl:template match="new_brewery">

		<xsl:element name='brewery'>
			<xsl:attribute name='id'><xsl:value-of select="$ID" /></xsl:attribute>
			<xsl:element name='name'><xsl:value-of select="name" /></xsl:element>
			<xsl:element name='uri'><xsl:value-of select="uri" /></xsl:element>
			<xsl:element name='address'>
				<xsl:element name='street'><xsl:value-of select="street" /></xsl:element>
				<xsl:element name='city'><xsl:value-of select="city" /></xsl:element>
				<xsl:element name='state'><xsl:value-of select="state" /></xsl:element>
				<xsl:element name='zip'><xsl:value-of select="zip" /></xsl:element>
				<xsl:element name='country'><xsl:value-of select="country" /></xsl:element>
			</xsl:element>
			<xsl:element name='phone'><xsl:value-of select="phone" /></xsl:element>
		</xsl:element>		

	</xsl:template>
	
</xsl:stylesheet>
