<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />
	
	<xsl:param name="XML_DIR"/>
	
	<xsl:include href="../std.xsl"/>
	
	<xsl:template match="/brewery">
		<xsl:element name="a">
			<xsl:attribute name="href">/brewery/<xsl:value-of select="@id"/></xsl:attribute>
			<xsl:value-of select="name"/>
		</xsl:element>
	</xsl:template>

	<xsl:template match="/styleguide">
		<xsl:param name="styleid"/>
		<xsl:element name="a">
			<xsl:attribute name="href">/style/<xsl:value-of select="$styleid"/></xsl:attribute>
			<xsl:value-of select="class[@type='beer']/category[@id=$styleid]/name|class[@type='beer']/category/subcategory[@id=$styleid]/name"/>
		</xsl:element>
	</xsl:template>


	<xsl:template match="/beer">
		
		<html xmlns:bl="http://beerliberation.com/">
			<head>
				<title><xsl:value-of select="name"/></title>
				<script type="text/javascript" src="/js/jquery-1.3.1.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/std.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/beer.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/superfish.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.hoverIntent.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.overlay-0.14.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.expose-0.14.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.jBreadCrumb.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery.easing.1.3.js"><xsl:text> </xsl:text></script>
				<script type="text/javascript" src="/js/jquery-autocomplete/jquery.autocomplete.js"><xsl:text> </xsl:text></script>

				<link href="/css/brewery.css"    rel="stylesheet" type="text/css" media="screen"/>
				<link href="/css/superfish.css"  rel="stylesheet" type="text/css" media="screen"/>
		        <link href="/css/Base.css" rel="stylesheet" type="text/css" media="screen"/>
		        <link href="/css/BreadCrumb.css" rel="stylesheet" type="text/css" media="screen"/>
		        <link href="/css/jquery-autocomplete/jquery.autocomplete.css" rel="stylesheet" type="text/css" media="screen"/>
			</head>
			<body>
				
				<xsl:call-template name="header">
					<xsl:with-param name="breadcrumbs" select="meta/breadcrumbs"/>
				</xsl:call-template>
				<div id="page_content">
					
					<xsl:element name='h1'>
						<xsl:attribute name='id'>beer_name</xsl:attribute>
						<xsl:attribute name='bl:beer_id'><xsl:value-of select="@id"/></xsl:attribute>
						<xsl:value-of select="name"/>
					</xsl:element>				
				
					<div>
						Made by: 
						<span id="beer_brewer">
							<xsl:choose>
								<xsl:when test="string-length(@brewery_id)">
									<xsl:apply-templates select="document(concat($XML_DIR,'/brewery/',@brewery_id,'.xml'))"/>
								</xsl:when>
								<xsl:otherwise>
									Unknown
								</xsl:otherwise>
							</xsl:choose>
						</span>
					</div>
						
					<h2>Description</h2>
					<div id="beer_descrip">
						<xsl:choose>
							<xsl:when test="string-length(description)">
								<xsl:value-of select="description"/><xsl:text> </xsl:text>
							</xsl:when>
							<xsl:otherwise>
								No description
							</xsl:otherwise>
						</xsl:choose>
					</div>
					<div>Alcohol %:
						<span id="beer_abv">
							<xsl:choose>
								<xsl:when test="string-length(abv)">
									<xsl:value-of select="abv"/>
								</xsl:when>
								<xsl:otherwise>
									Unknown
								</xsl:otherwise>
							</xsl:choose>
						</span>
					</div>
					<div>Style:
						<span id="beer_bjcp_style">
							<xsl:choose>
								<xsl:when test="string-length(@bjcp_style_id)">
									<xsl:apply-templates select="document(concat($XML_DIR,'/misc/styleguide2008.xml'))">
										<xsl:with-param name="styleid" select="@bjcp_style_id"/>
									</xsl:apply-templates>
								</xsl:when>
								<xsl:otherwise>
									Unknown
								</xsl:otherwise>
							</xsl:choose>
						</span>
					</div>
					<div>Average Price:<div id="beer_avg_price"><xsl:text> </xsl:text></div></div>
					
					<h2>Reviews</h2>
					<h2>People Who Like This Beer</h2>
					<h2>People Who Like This Beer Also Like...</h2>
					<h2>Where You Can Get This Beer</h2>
					<h2>Discussions</h2>
				
				</div>
				<xsl:call-template name="footer"/>
				
				
			</body>
		</html>
		
	</xsl:template>
	
</xsl:stylesheet>
