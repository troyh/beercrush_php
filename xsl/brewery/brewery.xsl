<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl">

	<xsl:output encoding="UTF-8" indent="yes" method="html" />
	
	<xsl:include href="../std.xsl"/>

	<xsl:template match="/brewery">
		
		<html xmlns:bc="http://beercrush.com/">
			<head>
				<title><xsl:value-of select="name"/></title>
				<script type="text/javascript" src="/js/jquery-1.3.1.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/brewery.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/superfish.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.hoverIntent.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.overlay-0.14.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.expose-0.14.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.jBreadCrumb.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.easing.1.3.js"><xsl:text> </xsl:text></script>
				<link href="/css/brewery.css"    rel="stylesheet" type="text/css" media="screen"/>
				<link href="/css/superfish.css"  rel="stylesheet" type="text/css" media="screen"/>
		        <link href="/css/Base.css" rel="stylesheet" type="text/css" media="screen"/>
		        <link href="/css/BreadCrumb.css" rel="stylesheet" type="text/css" media="screen"/>
			</head>
			<body>
				
				<div id="page_content">
					
				<xsl:element name='h1'>
					<xsl:attribute name='id'>brewery_name</xsl:attribute>
					<xsl:attribute name='bc:brewery_id'><xsl:value-of select="@id"/></xsl:attribute>
					<xsl:value-of select="name"/>
				</xsl:element>				
				
				<xsl:choose>				
					<xsl:when test="string-length(uri)">
						<div id="brewery_uri"><xsl:value-of select="uri"/></div>
						<xsl:element name='a'>
							<xsl:attribute name='href'><xsl:value-of select="uri"/></xsl:attribute>
							Visit web site
						</xsl:element>				
					</xsl:when>
					<xsl:otherwise>
						Add web site
					</xsl:otherwise>
				</xsl:choose>

				<div id="brewery_phone"><xsl:value-of select="phone" /></div>
		
				<div id="brewery_addr">
					<div>
						<xsl:value-of select="address/street"/>
					</div>
					<div>
						<xsl:value-of select="address/city"/>,
						<xsl:value-of select="address/state"/><xsl:text> </xsl:text>
						<xsl:value-of select="address/zip"/><xsl:text> </xsl:text>
						<xsl:value-of select="address/country"/>
					</div>
				</div>

				<h2>Reviews</h2>
				<h2>People Who Like This Brewer</h2>
				<h2>People Who Like This Brewer Also Like...</h2>
				<h2>Their Beers</h2>
				
				<div>
					<xsl:apply-templates select="php:function('get_document',concat('/meta/brewery/',@id))" mode="meta"/>
					<xsl:text> </xsl:text>
				</div>
				
				<h2>Where You Can Get Their Beers</h2>
				<h2>Discussions</h2>
				
				</div>

				<xsl:call-template name="header">
					<xsl:with-param name="breadcrumbs" select="meta/breadcrumbs"/>
				</xsl:call-template>
				<xsl:call-template name="footer"/>
				
				
			</body>
		</html>
		
	</xsl:template>

	<xsl:template match="/brewery/beerlist/beer" mode="meta">
		<div>
			<xsl:element name="a">
				<xsl:attribute name="href">/beer/<xsl:value-of select="@id" /></xsl:attribute>
				<xsl:value-of select="." />
			</xsl:element>
		</div>
	</xsl:template>


</xsl:stylesheet>
