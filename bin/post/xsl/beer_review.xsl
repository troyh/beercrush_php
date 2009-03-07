<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />

	<xsl:template match="/post">
		<xsl:element name='review'>
			<xsl:element name='user_id'><xsl:value-of select="cgi/cookies/userid" /></xsl:element>
			<xsl:element name='ipaddr'><xsl:value-of select="cgi/RemoteAddr" /></xsl:element>
			<xsl:element name='beer_id'><xsl:value-of select="doc/beer_review/beer_id" /></xsl:element>
			<xsl:element name='rating'><xsl:value-of select="doc/beer_review/rating" /></xsl:element>
		</xsl:element>		
	</xsl:template>
	
</xsl:stylesheet>
