<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />
	
	<xsl:include href="../std.xsl"/>

	<xsl:template match="brewery">
		<tr>
			<td>
				<xsl:element name='a'>
					<xsl:attribute name='href'>/beer/<xsl:value-of select="@id" /></xsl:attribute>
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

					<h1>All Breweries</h1>
					
					<!-- Alphabetic Navigation -->
					<div>
						<ul>
							<li><a href="./byletter/123">123</a></li>
							<li><a href="./byletter/A">A</a></li>
							<li><a href="./byletter/B">B</a></li>
							<li><a href="./byletter/C">C</a></li>
							<li><a href="./byletter/D">D</a></li>
							<li><a href="./byletter/E">E</a></li>
							<li><a href="./byletter/F">F</a></li>
							<li><a href="./byletter/G">G</a></li>
							<li><a href="./byletter/H">H</a></li>
							<li><a href="./byletter/I">I</a></li>
							<li><a href="./byletter/J">J</a></li>
							<li><a href="./byletter/K">K</a></li>
							<li><a href="./byletter/L">L</a></li>
							<li><a href="./byletter/M">M</a></li>
							<li><a href="./byletter/N">N</a></li>
							<li><a href="./byletter/O">O</a></li>
							<li><a href="./byletter/P">P</a></li>
							<li><a href="./byletter/Q">Q</a></li>
							<li><a href="./byletter/R">R</a></li>
							<li><a href="./byletter/S">S</a></li>
							<li><a href="./byletter/T">T</a></li>
							<li><a href="./byletter/U">U</a></li>
							<li><a href="./byletter/V">V</a></li>
							<li><a href="./byletter/W">W</a></li>
							<li><a href="./byletter/X">X</a></li>
							<li><a href="./byletter/Y">Y</a></li>
							<li><a href="./byletter/Z">Z</a></li>
						</ul>
					</div>
					
					There are <xsl:value-of select="format-number(count(brewery),',###')"/> breweries.
		
				</div>

				<xsl:call-template name="footer"/>
						
			</body>
		</html>
		
		
	</xsl:template>
	
</xsl:stylesheet>
