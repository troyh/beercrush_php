<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />
	
	<xsl:include href="../std.xsl"/>
	
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

					<!-- Alphabetic Navigation -->
					<div>
						<ul>
							<li><a href="/brewery/byletter/123.html">123</a></li>
							<li><a href="/brewery/byletter/A.html">A</a></li>
							<li><a href="/brewery/byletter/B.html">B</a></li>
							<li><a href="/brewery/byletter/C.html">C</a></li>
							<li><a href="/brewery/byletter/D.html">D</a></li>
							<li><a href="/brewery/byletter/E.html">E</a></li>
							<li><a href="/brewery/byletter/F.html">F</a></li>
							<li><a href="/brewery/byletter/G.html">G</a></li>
							<li><a href="/brewery/byletter/H.html">H</a></li>
							<li><a href="/brewery/byletter/I.html">I</a></li>
							<li><a href="/brewery/byletter/J.html">J</a></li>
							<li><a href="/brewery/byletter/K.html">K</a></li>
							<li><a href="/brewery/byletter/L.html">L</a></li>
							<li><a href="/brewery/byletter/M.html">M</a></li>
							<li><a href="/brewery/byletter/N.html">N</a></li>
							<li><a href="/brewery/byletter/O.html">O</a></li>
							<li><a href="/brewery/byletter/P.html">P</a></li>
							<li><a href="/brewery/byletter/Q.html">Q</a></li>
							<li><a href="/brewery/byletter/R.html">R</a></li>
							<li><a href="/brewery/byletter/S.html">S</a></li>
							<li><a href="/brewery/byletter/T.html">T</a></li>
							<li><a href="/brewery/byletter/U.html">U</a></li>
							<li><a href="/brewery/byletter/V.html">V</a></li>
							<li><a href="/brewery/byletter/W.html">W</a></li>
							<li><a href="/brewery/byletter/X.html">X</a></li>
							<li><a href="/brewery/byletter/Y.html">Y</a></li>
							<li><a href="/brewery/byletter/Z.html">Z</a></li>
						</ul>
					</div>

					<table> <tbody>
					
					 <!--
					
					 Can you believe XSLT 1.0+ doesn't provide a way to do this without this insane expression? If only you could compare strings,
                    which you can't, because the people who made XSLT are idiots.
					
					 -->

							<xsl:for-each select="brewery[not(starts-with(name,'A') or starts-with(name,'B') or starts-with(name,'C') or starts-with(name,'D') or starts-with(name,'E') or starts-with(name,'F') or starts-with(name,'G') or starts-with(name,'H') or starts-with(name,'I') or starts-with(name,'J') or starts-with(name,'K') or starts-with(name,'L') or starts-with(name,'M') or starts-with(name,'N') or starts-with(name,'O') or starts-with(name,'P') or starts-with(name,'Q') or starts-with(name,'R') or starts-with(name,'S') or starts-with(name,'T') or starts-with(name,'U') or starts-with(name,'V') or starts-with(name,'W') or starts-with(name,'X') or starts-with(name,'Y') or starts-with(name,'Z'))]">
								<xsl:sort select="name"/>
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
							</xsl:for-each>
						</tbody>
					</table>

					<xsl:text> </xsl:text>					
				</div>
			</body>
		</html>
			
	</xsl:template>
	
</xsl:stylesheet>
