<?xml version="1.0" encoding="UTF-8" ?>

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />

	<xsl:template name="header">
		<xsl:param name="Path1"/>
		<div id="header">
			
			<h1><a href="/">Beer Liberation</a></h1>

			<div>
	            <div class="breadCrumbHolder module">
					<div id="breadCrumb" class="breadCrumb module">
	                    <ul>
	                        <li><a href="../">Home</a></li>
	                        <li><a href="./"><xsl:value-of select="$Path1"/></a></li>
	                        <li>
								<xsl:element name="a">
		 							<xsl:attribute name="href"><xsl:value-of select="@id"/></xsl:attribute>
									<xsl:value-of select="name"/>
								</xsl:element>
							</li>
	                    </ul>
	                </div>
				</div>
	            <div class="chevronOverlay main"></div>
			</div>
			
			<ul id="header_menu" class="sf-menu">
				<li><a href="#">Breweries</a>
					<ul>
						<li id="header_menu_new_brewery"><a rel="#overlay_new_brewery">New Brewery</a></li>
						<li id="header_menu_brewery_list"><a href="/brewery/">Brewery List</a></li>
					</ul>
				</li>
				<li><a href="/beer/">Beers</a></li>
				<li><a href="/users/">Users</a></li>
			</ul>

		</div>
	</xsl:template>

	<xsl:template name="footer">
		<div id="footer">
			&#169;2009 Optional LLC
		</div>
		<div id="overlay_new_brewery" style="background-image:url(/img/overlay/white.png);"> 

			<h1>
				New Brewery
			</h1>
			<div>
				<table>
					<tbody>
						<tr><td>Name:</td><td><input id="new_brewery_name" type="text" name="name" /></td></tr>
						<tr><td>URL:</td><td><input id="new_brewery_uri" type="text" name="uri" /></td></tr>
						<tr><td>Phone:</td><td><input id="new_brewery_phone" type="text" name="phone" /></td></tr>
						<tr>
							<td>Address:</td>
							<td>
								<textarea id="new_brewery_address" name="address" rows="3" cols="30"><xsl:text> </xsl:text></textarea>
							</td>
						</tr>
						<tr>
							<td></td>
							<td><input id="new_brewery_save" type="button" value="Save" /><input id="new_brewery_cancel" type="button" value="Cancel" /></td>
						</tr>
					</tbody>
				</table>
				<div id="new_brewery_error"></div>
			</div>

		</div>
	</xsl:template>
	
</xsl:stylesheet>
