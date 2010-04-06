var login_data=new Object;

function set_login_cookies(login,date) 
{
	login_data=new Object;
	$.each(login,function(key,val) {
		switch (key) {
			case 'userid':
			case 'usrkey':
				$.cookie(key,val, { path: '/', expires: date});
				break;
			default:
				login_data[key]=val;
				break;
		}
	});
	$.cookie('login_data',JSON.stringify(login_data), { path: '/', expires: date});
}

function login()
{
	var email=$("#login_form input[name=email]").val();
	var passw=$("#login_form input[name=password]").val();

	$('#login_form').ajaxError(function(e,xhr,settings,exception){
		if (settings.url==$('#login_form').attr('action'))
		{
			$('#login_msg').text('Unable to login. Try again.');
			$('#login_msg').ajaxError(null);
		}
	});
	$.post($('#login_form').attr('action'),{email:email, password:passw},function(data) {
		$('#login_msg').text('');
		// Figure out how long to keep the cookies (session-only or a number of days)
		var date=null;
		if ($('#login_form input:checkbox[name=login_days]:checked').val()) {
			date = new Date();
			date.setTime(date.getTime() + ($('#login_form input:checkbox[name=login_days]').val() * 24 * 60 * 60 * 1000)); // set to expire in 1 day

			// Add login_days to data so we track it in a cookie
			data.login_days=parseInt($('#login_form input:checkbox[name=login_days]').val());
		}
		
		set_login_cookies(data,date);
		showusername();
	},"json");
}

function logout()
{
	// Clear login cookies
	$.cookie('userid',null,{path:'/'});
	$.cookie('usrkey',null,{path:'/'});
	$.cookie('login_data',null,{path:'/'});
	
	showlogin();
}

function showusername()
{
	$('#login').html((login_data.avatar?'<img src="'+login_data.avatar+'" />':'')+'You are logged in as <a href="/user/'+$.cookie('userid')+'">'+login_data.name+'</a> <a href="javascript:logout();">Logout</a>');
}

function showlogin()
{
	$('#login').html('\
	<form id="login_form" method="post" action="/api/login">\
	Sign in or <a href="/user/create">create an account</a>\
	Email:<input name="email" type="text" size="20" />\
	Password:<input name="password" type="password" size="10" />\
	<input value="Go" type="submit" />\
	<span id="login_dropdown" class="tiny"><input type="checkbox" name="login_days" value="1" />Keep me signed in on this computer</span>\
	<span id="login_msg"></span>\
	</form>');

	$('#login_form').submit(function(){login();return false;});
	$('#login_form').focusin(function(e){
		$('#login_dropdown').clearQueue().slideDown();
	});
	$('#login').focusout(function(e){
		$('#login_dropdown').delay(200).slideUp();
	});
	
}

function formatDates(selector)
{
	$(selector).each(function(i,e){
		d=new Date($(this).text());
		if (d.getTime())
		{
			now=new Date();
			diff=(now.getTime()-d.getTime())/1000;
			if (diff<0)
				$(this).text('just now'); // Times really can be negative due to variations in server & client clocks
			else if (diff<60)
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

function makeDocEditable(docSelector,docid_id,url,options)
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

				
				$(this).editable(function(value,settings) {

					// Get the name of this field
					var fieldname=$(this).attr('id').substr(prefix.length);
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
							
							if (options && typeof(options['beforeSave']) == 'function') {
								var more_data=options['beforeSave']();
								$.extend(editable_changes[$(docSelector).attr('id')],more_data);
							}

							// Post the data to the server
							$('#editable_save_msg').text('');
							$('#editable_save_msg').ajaxError(function(e,xhr,settings,exception){
								if (settings.url==url)
								{
									explanation=jQuery.parseJSON(xhr.responseText);
									$(this).text('Changes were not saved: '+explanation.exception.message);
									$(this).ajaxError(null);
								}
							});
							
							$.post(url,editable_changes[$(docSelector).attr('id')],function(data,status,req){
								$('#editable_save_msg').text('Changes saved!');

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

								if (options && typeof(options['afterSave']) == 'function') {
									options['afterSave']();
								}

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
