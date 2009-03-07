$(document).ready(function()
{
	$('header_menu').superfish();
	
	jQuery("#breadCrumb").jBreadCrumb();
	
	$('a[rel]').overlay({
		onBeforeLoad: function() {
			this.expose();
		},
		onClose: function(content) {
			$.unexpose();
		}
	});
	
	// See if the user is logged in
	userid=$.cookie('userid');
	usrkey=$.cookie('usrkey');
	if (userid!=null && usrkey!=null)
	{
		// User is logged in
		$('#header_login').html('Hi '+userid+'. <a href="/api/logout">Logout</a>');
	}
	else
	{
		// User is not logged in
		$('#header_login').html('<a href="/api/login?email=foo&password=bar">Login</a>');
	}
}
);
