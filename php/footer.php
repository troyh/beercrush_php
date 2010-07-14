	</div>
	<div id="footernav" class="cl">
		<div class="menu first">Your Account
			<ul>
				<li><a href="">Wishlist</a></li>
				<li><a href="">My Account</a></li>
				<li><a href="javascript: BeerCrush.logout();">Logout</a></li>
			</ul>
		</div>
		<div class="menu">Explore
			<ul>
				<li><a href="/beers/">Beers</a> <a href="/beers/az/">(a-z)</a></li>
				<li><a href="/breweries/">Breweries</a> <a href="">(a-z)</a></li>
				<li><a href="/places/">Beer Places</a> <a href="">(a-z)</a></li>
				<li><a href="/location/">Cities</a></li>
				<li><a href="/nearby">What's Nearby</a></li>
			</ul>
		</div>
		<div class="menu">Help/About
			<ul>
				<li><a href="/help">FAQ/Help</a></li>
				<li><a href="/tos">Terms of Service</a></li>
				<li><a href="/privacy">Privacy Policy</a></li>
				<li><a href="/contact">Contact Us</a></li>
				<li><a href="http://blog.beercrush.com/">Our Blog</a></li>
			</ul>
		</div>
		<div class="menu">For Business
			<ul>
				<li><a href="/business/brewery">Brewers</a></li>
				<li><a href="/business/place">Beer Place Owners</a></li>
				<li><a href="/business/distributor">Beer Distributors</a></li>
			</ul>
		</div>
		
		
	</div>
	<div id="popular">
		Popular Cities: 
			<a href="/location/United%20States/North%20Carolina/Asheville/">Asheville</a> |
			<a href="/location/United%20States/Georgia/Atlanta/">Atlanta</a> |
			<a href="/location/United%20States/Texas/Austin/">Austin</a> |
			<a href="/location/United%20States/Massachusetts/Boston/">Boston</a> |
			<a href="/location/United%20States/Illinois/Chicago/">Chicago</a> |
			<a href="/location/United%20States/Texas/Dallas/">Dallas</a> |
			<a href="/location/United%20States/Colorado/Denver/">Denver</a> |
			<a href="/location/United%20States/Texas/Houston/">Houston</a> |
			<a href="/location/United%20States/California/Los%20Angeles/">Los Angeles</a> |
			<a href="/location/United%20States/Florida/Miami/">Miami</a> |
			<a href="/location/United%20States/Minnesota/Minneapolis/">Minneapolis</a> |
			<a href="/location/United%20States/New%20York/New%20York/">New York</a> |
			<a href="/location/United%20States/Pennsylvania/Philadelphia/">Philadelphia</a> |
			<a href="/location/United%20States/Arizona/Phoenix/">Phoenix</a> |
			<a href="/location/United%20States/Oregon/Portland/">Portland</a> |
			<a href="/location/United%20States/California/San%20Diego/">San Diego</a> |
			<a href="/location/United%20States/California/San%20Francisco/">San Francisco</a> |
			<a href="/location/United%20States/Washington/Seattle/">Seattle</a> |
			<a href="/location/United%20States/Washington%20D.C./">Washington D.C.</a> |
			<a href="/location/United%20Kingdom/London/">London</a> |
			<a href="/location/Germany/Berlin/">Berlin</a> |
			<a href="/location/Belgium/Brussel/">Brussels</a> |
		
	</div>
</div>

<div id="copyright">
	<div id="pagemodtime">Page last modified: <span class="datestring"><?=date('D, d M Y H:i:s O',time())?></span></div>
	&copy; <!-- YEAR --> Beer Crush (r<!-- SVNVERSION -->)
</div>

<script type="text/javascript">

function BeerCrushMain()
{
	if ($.cookie('login_data'))
		login_data=jQuery.parseJSON($.cookie('login_data'));
		
	if (BeerCrush.get_user_id())
	{
		BeerCrush.showusername();
		if (login_data.login_days)
		{
			// Extend login so that the user stays logged in if they continue to use the site
			date = new Date();
			date.setTime(date.getTime() + (login_data.login_days * 24 * 60 * 60 * 1000)); // set to expire in the future
			
			// Use tmp_data so that we don't put userid and usrkey in the global
			// login_data, which may be used to set a cookie, in which case the cookies
			// would have duplicate data (in the login_data cookie and in the userid and
			// usrkey cookies).
									
			var tmp_data=login_data;
			tmp_data.userid=$.cookie('userid');
			tmp_data.usrkey=$.cookie('usrkey');
			BeerCrush.set_login_cookies(tmp_data,date);
		}
	}
	else
	{
		// Put login window at top of page
		BeerCrush.showlogin();
	}
	
	formatDates('.datestring');

	if (typeof(window['pageMain'])!='undefined' && jQuery.isFunction(pageMain))
		pageMain();
}

google.setOnLoadCallback(BeerCrushMain);

</script>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-11292015-1");
pageTracker._trackPageview();
} catch(err) {}</script>
</body>
</html>
