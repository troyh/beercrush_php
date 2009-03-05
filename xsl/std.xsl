<?xml version="1.0" encoding="UTF-8" ?>

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="xml" />
	
	<xsl:template match="breadcrumbs">
		<div class="breadCrumbHolder module">
			<div id="breadCrumb" class="breadCrumb module">
                <ul>
                    <li><a href="/">Home</a></li>
					<xsl:for-each select="crumb">
	                    <li>
							<xsl:element name="a">
	 							<xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute>
								<xsl:value-of select="."/>
							</xsl:element>
						</li>
					</xsl:for-each>
                </ul>
            </div>
		</div>
        <div class="chevronOverlay main"><xsl:text> </xsl:text></div>
	</xsl:template>

	<xsl:template name="header">
		<xsl:param name="breadcrumbs"/>
		<div id="header">
			
			<h1><a href="/">BeerCrush</a></h1>

			<div>
				<xsl:apply-templates select="$breadcrumbs"/>
			</div>
			
			<ul id="header_menu" class="sf-menu">
				<li><a href="/brewery/">Breweries</a>
					<ul>
						<li><a>Brewers</a></li>
						<li><a>Brewpubs</a></li>
						<li id="header_menu_brewery_list"><a href="/brewery/">All Brewers</a></li>
						<li id="header_menu_new_brewery"><a rel="#overlay_new_brewery">New Brewery</a></li>
					</ul>
				</li>
				<li><a href="/beer/">Beers</a>
					<ul>
						<li><a>Styles</a></li>
						<li><a>Homebrew</a></li>
						<li id="header_menu_new_beer"><a rel="#overlay_new_beer">New Beer</a></li>
					</ul>
				</li>
				<li><a href="/users/">Users</a>
				<ul>
					<li><a>New</a></li>
					<li><a>By Name</a></li>
					<li><a>Activity</a></li>
				</ul>
				</li>
				<li><a href="/users/">Places</a>
					<ul>
						<li><a>United States</a>
							<ul>
								<li>West Coast</li>
								<li>East Coast</li>
								<li>Midwest</li>
								<li>South</li>
							</ul>
						</li>
						<li><a>Europe</a></li>
						<li><a>Asia</a></li>
						<li><a>South America</a></li>
					</ul>
				</li>
				<li><a href="/users/">Events</a>
				<ul>
					<li><a>Upcoming</a></li>
					<li><a>January</a></li>
					<li><a>February</a></li>
					<li><a>March</a></li>
					<li><a>April</a></li>
					<li><a>May</a></li>
					<li><a>June</a></li>
					<li><a>July</a></li>
					<li><a>August</a></li>
					<li><a>September</a></li>
					<li><a>October</a></li>
					<li><a>November</a></li>
					<li><a>December</a></li>
				</ul>
				</li>
				<li><a href="/news/">News</a></li>
			</ul>

		</div>
	</xsl:template>

	<xsl:template name="footer">
		<div id="footer">
			&#169;2009 Optional LLC
		</div>
		
		<div>
			<div id="overlay_new_brewery" class="overlay" style="background-image:url(/img/overlay/white.png);"> 

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
		</div>

		<div>
			<div id="overlay_new_beer" class="overlay" style="background-image:url(/img/overlay/white.png);"> 

				<h1>
					New Beer
				</h1>
				<div>
					<table>
						<tbody>
							<tr><td>Name:</td><td><input id="new_beer_name" type="text" name="name" /></td></tr>
							<tr>
								<td></td>
								<td><input id="new_beer_save" type="button" value="Save" /><input id="new_beer_cancel" type="button" value="Cancel" /></td>
							</tr>
						</tbody>
					</table>
					<div id="new_beer_error"></div>
				</div>

			</div>
		</div>
	</xsl:template>
	
</xsl:stylesheet>
