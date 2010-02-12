	</div>
	<div id="footer_spacer">
	</div>
	<div id="footer">
		&copy; <!-- YEAR --> Beer Crush (r<!-- SVNVERSION -->)
		<div id="pagemodtime">Page last modified: <span class="datestring"><?=date('D, d M Y H:i:s O',time())?></span></div>
	</div>
	
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

function formatDates(selector)
{
	$(selector).each(function(i,e){
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
}

var editable_changes=new Object;

function makeDocEditable(docSelector,docid_id,url)
{
	// Iterate each child, making every non-input element that has an id prefixed with
	// '<STR>_', where <STR> is the ID of selector, into an editable field
	if ($(docSelector).attr('id').length)
	{
		editable_changes[$(docSelector).attr('id')]=new Object;
		
		var prefix=$(docSelector).attr('id')+'_';
		$(docSelector+' *').each(function() {
			if ($(this).get(0).tagName!='INPUT' && ($(this).attr('id').substr(0,prefix.length) == prefix))
			{ // This is a field we need to make editable

				// Get the name of this field
				var fieldname=$(this).attr('id').substr(prefix.length);
				
				$(this).editable(function(value,settings) {
					
					editable_changes[$(docSelector).attr('id')][fieldname]=value;
					
					$(docSelector+' .editable_savechanges_button').each(function() {
						
						// Unhide the button
						$(this).removeClass('hidden');

						// Unbind the click function, so we don't add multiple ones
						$(this).unbind('click');
						// Put onclick functions on each Save Changes button
						$(this).click(function(){
							// TODO: disable all the buttons so they can't be pressed while the POST request is happening
						
							// Add in document id to change object
							editable_changes[$(docSelector).attr('id')][docid_id]=$('#'+docid_id).val();

							// Post the data to the server
							console.log(editable_changes[$(docSelector).attr('id')]);
							
							$('#savemsg').text('');
							$('#savemsg').ajaxError(function(e,xhr,settings,exception){
								if (settings.url==url)
								{
									explanation=jQuery.parseJSON(xhr.responseText);
									$(this).text('Changes were not saved: '+explanation.exception.message);
									$(this).ajaxError(null);
								}
							});
							
							$.post('/api/beer/edit',editable_changes[$(docSelector).attr('id')],function(data,status,req){
								$('#savemsg').text('Changes saved!');

								// Hide all the save & cancel buttons
								$(docSelector+' .editable_savechanges_button').each(function(){$(this).addClass('hidden');});
								$(docSelector+' .editable_cancelchanges_button').each(function(){$(this).addClass('hidden');});

								// Put the values from the response, they could be slightly different than what the user actually typed.
								$(docSelector+' *').each(function(){
									if ($(this).attr('id').substr(0,prefix.length) == prefix)
									{
										fieldname=$(this).attr('id').substr(prefix.length);
										$(this).text(data[fieldname]);
									}
								});

								// Change the document modtime
								// if (data.meta.mtime)
								// {
								// 	mtime=new Date(data.meta.mtime * 1000);
								// 	$(docSelector+'_lastmodified').text(mtime.toLocaleString());
								// 	formatDates('.datestring');
								// }

							},'json');
						
							// Hide all the save & cancel buttons
							$(docSelector+' .editable_savechanges_button').each(function(){$(this).addClass('hidden');});
							$(docSelector+' .editable_cancelchanges_button').each(function(){$(this).addClass('hidden');});
							
							return false;
						});
					});

					$(docSelector+' .editable_cancelchanges_button').each(function(){

						// Unhide the button
						$(this).removeClass('hidden');

						// Unbind the click function, so we don't add multiple ones
						$(this).unbind('click');
						// Put onclick functions on each Cancel Changes button
						$(this).click(function(){
							// TODO: put all the old data back
							
							// Hide all the save & cancel buttons
							$(docSelector+' .editable_savechanges_button').each(function(){$(this).addClass('hidden');});
							$(docSelector+' .editable_cancelchanges_button').each(function(){$(this).addClass('hidden');});
						});
					});
					
					return value;
				}, {
					type: $(this).hasClass('editable_textarea')?'textarea':'text',
					cancel: 'Cancel',
					submit: 'OK'
				});
			}
		});
	}
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
