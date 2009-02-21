<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />
	
	<xsl:include href="../std.xsl"/>

	<xsl:template match="brewery">
		<tr>
			<td>
				<xsl:element name='a'>
					<xsl:attribute name='href'><xsl:value-of select="@id" /></xsl:attribute>
					<xsl:value-of select="name" />
				</xsl:element>
			</td>
			<td>
				<xsl:value-of select="address/city" />, <xsl:value-of select="address/state" /><xsl:text> </xsl:text><xsl:value-of select="address/country" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="/breweries">
		<html>
			<head>
				<title>Breweries</title>
				<script type="text/javascript" src="/js/jquery-1.3.1.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/brewery.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/superfish.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.hoverIntent.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.overlay-0.14.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.expose-0.14.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.jBreadCrumb.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.easing.1.3.js"><xsl:text> </xsl:text></script>
				<link href="/css/brewery.css" type="text/css" rel="stylesheet" media="screen" />
				<link href="/css/superfish.css" type="text/css" rel="stylesheet"  media="screen" />
		        <link href="/css/Base.css"       rel="stylesheet" type="text/css" media="screen"/>
		        <link href="/css/BreadCrumb.css" rel="stylesheet" type="text/css" media="screen"/>
			</head>
			<body>
			
				<xsl:call-template name="header">
					<xsl:with-param name="breadcrumbs" select="meta/breadcrumbs"/>
				</xsl:call-template>
				<div id="page_content">

					<table>
						<tbody>
							<xsl:apply-templates select="brewery" />
						</tbody>
					</table>
		
				</div>

				<xsl:call-template name="footer"/>
						
			</body>
		</html>
		
		
	</xsl:template>
	
</xsl:stylesheet>
