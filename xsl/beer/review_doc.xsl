<?xml version="1.0" encoding="UTF-8" ?>
<!--
	untitled
	Created by Troy Hakala on 2009-04-17.
	Copyright (c) 2009 __MyCompanyName__. All rights reserved.
-->

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />

	<xsl:param name="user_id"/>
	<xsl:param name="beer_id"/>
	<xsl:param name="rating"/>
	<xsl:param name="srm"/>
	<xsl:param name="body"/>
	<xsl:param name="bitterness"/>
	<xsl:param name="sweetness"/>
	<xsl:param name="aftertaste"/>
	<xsl:param name="comments"/>
	<xsl:param name="food_recommended"/>
	<xsl:param name="price"/>
	<xsl:param name="place"/>
	<xsl:param name="size"/>

	<xsl:template match="/">
		<xsl:element name="review">
			<xsl:attribute name="user_id"><xsl:value-of select="$user_id"/></xsl:attribute>
			<xsl:attribute name="beer_id"><xsl:value-of select="$beer_id"/></xsl:attribute>
			<xsl:attribute name="datetime"></xsl:attribute>
			<xsl:element name='rating'>
				<xsl:attribute name='value'><xsl:value-of select="$rating"/></xsl:attribute>
			</xsl:element>
			<xsl:element name='color'>
				<xsl:attribute name='srm'><xsl:value-of select="$srm"/></xsl:attribute>
			</xsl:element>
			<xsl:element name='body'>
				<xsl:attribute name='value'><xsl:value-of select="$body"/></xsl:attribute>
			</xsl:element>
			<xsl:element name='bitterness'>
				<xsl:attribute name='value'><xsl:value-of select="$bitterness"/></xsl:attribute>
			</xsl:element>
			<xsl:element name='sweetness'>
				<xsl:attribute name='value'><xsl:value-of select="$sweetness"/></xsl:attribute>
			</xsl:element>
			<xsl:element name='aftertaste'>
				<xsl:attribute name='value'><xsl:value-of select="$aftertaste"/></xsl:attribute>
			</xsl:element>
			<aromas>
				<aroma></aroma>
				<aroma></aroma>
				<aroma></aroma>
			</aromas>
			<flavors>
				<flavor></flavor>
				<flavor></flavor>
				<flavor></flavor>
			</flavors>
			<comments><xsl:value-of select="$comments"/></comments>
			<size><xsl:value-of select="$size"/></size>
			<purchase>
				<xsl:element name='price'>
					<xsl:attribute name='value'><xsl:value-of select="$price"/></xsl:attribute>
				</xsl:element>
				<place><xsl:value-of select="$place"/></place>
			</purchase>
			<xsl:element name='drankwithfood'>
				<xsl:attribute name='recommended'><xsl:value-of select="$food_recommended"/></xsl:attribute>
			</xsl:element>
		</xsl:element>
	</xsl:template>
	
</xsl:stylesheet>
