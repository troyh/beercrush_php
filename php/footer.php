	</div>
	<div id="footer_spacer">
	</div>
	<div id="footer">
		&copy; <!-- YEAR --> Beer Crush (r<!-- SVNVERSION -->)
		<div id="pagemodtime">Page last modified: <span class="datestring"><?=date('D, d M Y H:i:s O',time())?></span></div>
	</div>
	
<script type="text/javascript">
google.load("jquery","1.4.1");
</script>
<script type="text/javascript" src="/js/jquery.cookie.js"></script>
<script type="text/javascript">

function login()
{
	$.post("/api/login",{email:$("#login_email").val(), password:$("#login_password").val()},function(data) {
		var date = new Date();
		date.setTime(date.getTime() + (1 * 24 * 60 * 60 * 1000)); // set to expire in 1 day

		$.cookie('userid',data.userid, { path: '/', expires: date });
		$.cookie('usrkey',data.usrkey, { path: '/', expires: date });
		$.cookie('name',data.name, { path: '/', expires: date });
		
		showusername();
	},"json");
}

function logout()
{
	// Clear login cookies
	$.cookie('userid',null);
	$.cookie('usrkey',null);
	$.cookie('name',null);
	
	showlogin();
}

function showusername()
{
	$('#login').html('You are logged in as '+$.cookie('name')+' <a href="javascript:logout();">Logout</a>');
}

function showlogin()
{
	$('#login').html('\
	Email:<input id="login_email" name="email" type="text" size="10" />\
	Password:<input id="login_password" type="password" size="10" />\
	<input value="Go" type="button" onclick="javascript:login()" />');
}

function BeerCrushMain()
{
	if ($.cookie('userid'))
	{
		showusername();
	}
	else
	{
		// Put login window at top of page
		showlogin();
	}

	$('.datestring').each(function(i,e){
		d=new Date($(this).text());
		if (d.getTime())
		{
			now=new Date();
			diff=(now.getTime()-d.getTime())/1000;
			if (diff<60)
				$(this).text(Math.round(diff)+' seconds ago');
			else if ((diff/60) < 60)
				$(this).text(Math.round(diff/60)+' minutes ago');
			else if (diff/(60*60) < 24)
				$(this).text(Math.round(diff/(60*60))+' hours ago');
			else if (diff/(60*60*24) < 7)
				$(this).text(Math.round(diff/(60*60*24))+' days ago');
			else if (diff/(60*60*24*7) < 4)
				$(this).text(Math.round(diff/(60*60*24*7))+' weeks ago');
			else if (diff/(60*60*24*7*4) < 12)
				$(this).text(Math.round(diff/(60*60*24*7*4))+' months ago');
			else if (diff/(60*60*24*7*4*12) < 10)
				$(this).text(Math.round(diff/(60*60*24*7*4*12))+' years ago');
			else
			{
				$(this).text('a long time ago');
			}
		}
	});
	
	if (jQuery.isFunction('pageMain'))
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
