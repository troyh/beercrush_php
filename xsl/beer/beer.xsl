<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />
	
	<xsl:include href="../std.xsl"/>
	
	<xsl:template match="/brewery">
		<xsl:element name="a">
			<xsl:attribute name="href">/brewery/<xsl:value-of select="@id"/></xsl:attribute>
			<xsl:value-of select="name"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="/beer">
		
		<html xmlns:bl="http://beerliberation.com/">
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
				
				<xsl:call-template name="header">
					<xsl:with-param name="Path1" select="string('Beer')"/>
				</xsl:call-template>
				<div id="page_content">
					
				<xsl:element name='h1'>
					<xsl:attribute name='id'>name</xsl:attribute>
					<xsl:attribute name='bl:beer_id'><xsl:value-of select="@id"/></xsl:attribute>
					<xsl:value-of select="name"/>
				</xsl:element>				
				
				<div>
					Made by: <xsl:apply-templates select="document(concat('/home/troy/beerliberation/xml/brewery/',@brewery_id,'.xml'))"/>
				</div>
								
				<div id="beer_descrip"><xsl:value-of select="description"/></div>

				
				</div>
				<xsl:call-template name="footer"/>
				
				
			</body>
		</html>
		
	</xsl:template>
	
</xsl:stylesheet>
