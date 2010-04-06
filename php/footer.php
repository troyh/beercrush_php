	</div>
	<div id="footer_spacer"></div>
	<div id="footer">
		&copy; <!-- YEAR --> Beer Crush (r<!-- SVNVERSION -->)
		<div id="pagemodtime">Page last modified: <span class="datestring"><?=date('D, d M Y H:i:s O',time())?></span></div>
	</div>
</div>
<script type="text/javascript">

function BeerCrushMain()
{
	if ($.cookie('login_data'))
		login_data=jQuery.parseJSON($.cookie('login_data'));
		
	if ($.cookie('userid'))
	{
		showusername();
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
			set_login_cookies(tmp_data,date);
		}
	}
	else
	{
		// Put login window at top of page
		showlogin();
	}
	
	formatDates('.datestring');

	$("#searchbox").autocomplete('/api/autocomplete.fcgi',{
		"extraParams": {
			"dataset": function() { return $("#searchform input:radio[name='dt']:checked").val(); }
		}
	});

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
