var login_data=new Object;

function get_user_id() {
	var user_id=$.cookie('userid');
	if (typeof(user_id)=='undefined')
		return null;
	return user_id;
}

function set_login_cookies(login,expiry_days) 
{
	// Figure out how long to keep the cookies (session-only or a number of days)
	var date=null;
	if ($('#login_form input:checkbox[name=login_days]:checked').val()) {
		date = new Date();
		date.setTime(date.getTime() + ($('#login_form input:checkbox[name=login_days]').val() * 24 * 60 * 60 * 1000)); // set to expire in 1 day

		// Add login_days to data so we track it in a cookie
		data.login_days=expiry_days;
	}

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

function login(email,password,success_func,fail_func) {
	$.ajax({
		type: 'POST',
		url: '/api/login',
		data: {
			email:email, 
			password:password
		},
		success: function(data) {
			if (success_func)
				success_func(data);
		},
		error: function(xhr,status,err) {
			if (fail_func)
				fail_func(xhr.status);
		},
		dataType: 'json'
	});
}

function logout()
{
	// Clear login cookies
	$.cookie('userid',null,{path:'/'});
	$.cookie('usrkey',null,{path:'/'});
	$.cookie('login_data',null,{path:'/'});
	
	showlogin();
}

function create_account(email,password,success_func,fail_func) {
	$.ajax({
		type: 'POST',
		url: '/api/createlogin',
		data: {
			email: email,
			password: password
		},
		success: success_func,
		error: function(xhr,status,err) {
			if (fail_func)
				fail_func(xhr.status);
		},
		dataType: 'json'
	});
}

function showusername()
{
	$('#login').html((login_data.avatar?'<img src="'+login_data.avatar+'" />':'')+'Cheers, <a href="/user/'+$.cookie('userid')+'">'+login_data.name+'</a>! <a href="javascript:logout();">Logout</a>');
}

function show_login_dialog(title,success_func) {

	if ($('#inplacelogin').size()==0) {
		$('#page_wrap').append(
			'<div id="inplacelogin" class="hidden">'+
				'<form method="post" action="/api/login">'+
					'<label for="email">Email: </label><input name="email" type="text" size="25" />'+
					'<label for="password">Password: </label><input name="password" type="password" size="10" /><input type="checkbox" name="login_days" value="1" /><label for="login_days" class="tiny">Remember me</label>'+
					'<div id="inplacelogin_buttons">'+
						'<input value="Sign In" type="submit" />'+
			 			'or <input value="Create Account" type="button" />'+
					'</div>'+
					'<div id="login_dialog_msg"></div>'+
				'</form>'+
			'</div>');
	}

	$('#inplacelogin form').submit(function(evt) {
		$('#login_dialog_msg').hide('blind','fast');
		$('#login_dialog_msg').removeClass('ui-state-error');

		// Validate email and password
		var email=$('input[name="email"]',evt.target).val().replace(/\s+/,''); // Remove all spaces
		var password=$('input[name="password"]',evt.target).val().replace(/^\s+/,'').replace(/\s+$/,''); // Trim it
		
		if (email.length==0) {
			$('#login_dialog_msg').addClass('ui-state-error');
			$('#login_dialog_msg').show('blind','slow');
			$('#inplacelogin').dialog('widget').effect('shake','fast');
			$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Email must be filled in.');
		}
		else if (password.length==0) {
			$('#login_dialog_msg').addClass('ui-state-error');
			$('#login_dialog_msg').show('blind','slow');
			$('#inplacelogin').dialog('widget').effect('shake','fast');
			$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Password must be filled in.');
		}
		else if (email.match(/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i)==null) {
			// Regex from http://www.regular-expressions.info/email.html
			$('#login_dialog_msg').addClass('ui-state-error');
			$('#login_dialog_msg').show('blind','slow');
			$('#inplacelogin').dialog('widget').effect('shake','fast');
			$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Email format is not valid; check for typos.');
		}
		else {
			login(email,password,function(data) {
				$('#inplacelogin').dialog('close');

				set_login_cookies(data,parseInt($('#inplacelogin input:checkbox[name=login_days]').val()));
				showusername();

				if (success_func)
					success_func();
			},
			function(status) {
				switch (status) {
					case 403:
						$('#login_dialog_msg').addClass('ui-state-error');
						$('#login_dialog_msg').show('blind','slow');
						$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Hmm, that combination doesn\'t match our files.  <a href="" onclick="forgot_password(event);return false;">Forgot your password</a>?');
						break;
					case 405:
						$('#login_dialog_msg').addClass('ui-state-error');
						$('#login_dialog_msg').show('blind','slow');
						$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>No account with that email.  Create one.');
						break;
					default:
						$('#login_dialog_msg').addClass('ui-state-error');
						$('#login_dialog_msg').show('blind','slow');
						$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>It didn\'t work and I don\'t know why.');
						break;
				}
			});
		}
		return false;
	});
	
	$('#inplacelogin input:button').click(function(evt) {
		var email=$('input[name="email"]',$('#inplacelogin form')).val().replace(/\s+/,''); // Remove all spaces
		var password=$('input[name="password"]',$('#inplacelogin form')).val().replace(/^\s+/,'').replace(/\s+$/,''); // Trim it
		
		if (email.length==0) {
			$('#inplacelogin').dialog('widget').effect('shake');
			$('#login_dialog_msg').addClass('ui-state-error');
			$('#login_dialog_msg').show('blind','slow');
			$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Email must be filled in.');
		}
		else if (password.length==0) {
			$('#inplacelogin').dialog('widget').effect('shake');
			$('#login_dialog_msg').addClass('ui-state-error');
			$('#login_dialog_msg').show('blind','slow');
			$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Password must be filled in.');
		}
		else if (email.match(/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i)==null) {
			// Regex from http://www.regular-expressions.info/email.html
			$('#inplacelogin').dialog('widget').effect('shake');
			$('#login_dialog_msg').addClass('ui-state-error');
			$('#login_dialog_msg').show('blind','slow');
			$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Email format is not valid; check for typos.');
		}
		else {
		
			create_account(email,password,function(){
				// Automatically log the user in
				login(email,password);
			},function(status){
				switch (status) {
					case 409: // Email already registered...
						// Maybe they really meant to login? Try that.
						login(email,password,function(data) {
							$('#inplacelogin').dialog('close');

							set_login_cookies(data,parseInt($('#inplacelogin input:checkbox[name=login_days]').val()));
							showusername();

							if (success_func)
								success_func();
						},function(status) {
							$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>That email address is already registered. <a href="" onclick="forgot_password(event);return false;">Forgot your password</a>?');
						});
						break;
					default:
						$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>It didn\'t work and I don\'t know why.  Try again.  If you still have problems <a href="/contact">aontact us</a>');
						break;
				}
			});
		}
	});
	
	$('#inplacelogin').dialog({
		modal:true,
		title:title,
		resizable: false,
		minWidth: 400
	});
}

function forgot_password(evt) {
	var email=$(evt.target).parentsUntil('form').parent().find('input[name=email]').val();//.replace(/^\s+/,'').replace(/\s+$/,'');
	if (email.length) {
		$.ajax({
			type: 'POST',
			url: '/api/forgotpassword',
			data: {'email': email},
			success:function(data){
				// TODO: tell user to expect email
				$('#login_dialog_msg').html('Check your mail.');
			},
			error: function(xhr,status,err) {
				switch (xhr.status) {
					case 406: // Email not found
						$('#login_dialog_msg').html('No account with that email address. Try creating an account instead.');
						break;
					case 404: // Password not found
					default:
						$('#login_dialog_msg').html('Unable to email your password.');
						break;
				}
			}
		});
	}
}

function showlogin()
{
	$('#login').html('<div><a href="">Login</a> or <a href="">Create Account</a></div>');

	$('#login a').click(function() {
		show_login_dialog('Login');
		return false;
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

function flatten_json(data,prefix) {
	var flattened={};

	var n='';
	if (prefix)
		n=prefix+':';

	$.each(data,function(k,v) {
		if (typeof(v)=='object')
			$.extend(flattened,flatten_json(v,n+k));
		else
			flattened[n+k]=v;
	});
	
	return flattened;
}

////////////////////////////////////////////////
// Loading Spinner jQuery Plugin
////////////////////////////////////////////////
(function($){
$.fn.spinner = function(options) {
	
	if (typeof(options)=='string') {
		// It's a method call
		return this.each(function() {
			if (options=='close') {
				$('div.spinner',this).remove();
			}
		});
	}
	
	var defaults={
		style: {
			width: 100,
			height: 100,
			background: 'rgba(0,0,0,.25)'
		}
	};
	
	var options=$.extend(defaults,options);

	return this.each(function(){
		
		// Find the center of the element
		var x=$(this).offset().left+($(this).width()/2)-options.style.width;
		var y=$(this).offset().top+($(this).height()/2)-options.style.height;
		
		$(this).append('<div class="spinner" style="position:absolute;left:'+
			x+';top:'+y+';width:'+options.style.width+';height:'+options.style.height+';background:'+options.style.background+
			';"><img src="/img/loading.gif" style="position:relative;top:'+((options.style.height-32)/2)+'px;left:'+((options.style.width-32)/2)+'px" /></div>');
	});
}
})(jQuery);

////////////////////////////////////////////////
// editabledoc jQuery Plugin
////////////////////////////////////////////////
(function($){
$.fn.editabledoc = function(posturl,options) {
	
	var defaults={
		args: {},
		fields: {},
		stripprefix: '',
		edit_button_selector: '#edit_button',
		done_button_text: 'Done'
	};
	
	var options=$.extend(defaults,options);

	return this.each(function() {
		
		var doc=$(this);

		$(options.edit_button_selector).toggle(function(){
			$(this).data('orig_value',$(this).attr('value'));
			$(this).attr('value',options.done_button_text);
		
			$(doc).hide();
			$('#'+$(doc).attr('id')+'_edit').show();
		
			// For each child element that has an ID and a matching edit version ID, assign the value
			$(doc).find('[id]').each(function(){
				var edit_id=$(this).attr('id')+'_edit';
				if ($('#'+edit_id).size()) { // If a companion _edit field exists...
			
					var val=null;
			
					if (options.fields && options.fields[$(this).attr('id')] && options.fields[$(this).attr('id')].displayValueToEditValue) { // Call user function to get the value
						val=options.fields[$(this).attr('id')].displayValueToEditValue();
					}
					else { // The standard way
						switch ($(this).get(0).tagName) {
							case 'INPUT':
								val=$(this).val();
								break;
							case 'DIV':
							case 'SPAN':
								val=$(this).text();
								break;
							default:
								break;
						}
					}
			
					if (val!=null) {
						var ev=$('#'+edit_id).get(0);
						switch (ev.tagName) {
							case 'INPUT':
							case 'TEXTAREA':
								$(ev).val(val);
								break;
							case 'SELECT':
								// Find the option element that has the value we want
								$(ev).children().each(function(i,e){
									if ($(e).val()==val)
										$(e).attr('selected','selected');
								});
								break;
							default:
								break;
						}
					}
				}
			});
		},
		function() {
			$(this).attr('value',$(this).data('orig_value'));
			$(doc).show();
			$('#'+$(doc).attr('id')+'_edit').hide();

			var edited_fields=new Object;
		
			// Collect all the info and post it to the server
			$(doc).find('[id]').each(function(idx,edit_elem){
				var edit_id=$(edit_elem).attr('id')+'_edit';

				if ($('#'+edit_id).size()) { // If a companion _edit field exists...
					// Get the value from the edit field
					var val=null;
					var fld=$('#'+edit_id).get(0);
					switch (fld.tagName) {
						case 'INPUT':
						case 'TEXTAREA':
							val=$(fld).val();
							break;
						case 'SELECT':
							// Find the value from the option elements
							val=$(fld).val();
							break;
						default:
							break;
					}
				
					if (val!=null) {
						var display_val=val;
						if (options.fields && options.fields[$(fld).attr('id')] && options[$(fld).attr('id')].editValueToDisplayValue) {
							display_val=options.fields[$(fld).attr('id')].editValueToDisplayValue(val);
						}
					
						// Put the value back in the static form for display and if it's changed, 
						// add it to the list of fields that need to be sent to the server
					
						var field_edited=false;
					
						switch ($(edit_elem).get(0).tagName) {
							case 'SELECT':
							case 'TEXTAREA':
							case 'INPUT':
								if ($(edit_elem).val()!=val) {
									$(edit_elem).val(val);
									field_edited=true;
								}
								break;
							default: // H1s, H2s, DIVs, SPANs, etc.
								if ($(edit_elem).text()!=val) {
									$(edit_elem).html(val);
									field_edited=true;
								}
								break;
						}
					
						if (field_edited) {
							if (options.fields && options.fields[$(fld).attr('id')] && options.fields[$(fld).attr('id')].saveEditValue) {
								options.fields[$(fld).attr('id')].saveEditValue(val);
							}
						
							edited_fields[$(fld).attr('id').replace(/_edit$/,'')]=val;
						}
					
					}
				}
			});
		
			var server_to_local_name_map={};

			$.each(edited_fields,function(k,v){
				var server_side_name;
				if (options.fields[k] && options.fields[k].postName) {
					server_side_name=options.fields[k].postName;
				}
				else if (options.stripprefix && options.stripprefix.length) {
					var regex=new RegExp(options.stripprefix);
					server_side_name=k.replace(regex,'');
				}
				options.args[server_side_name]=v;
				
				// Remember all the server_side_names
				server_to_local_name_map[server_side_name]=k;
			});

			if ($.isEmptyObject(edited_fields)==false) {
				// Send edits to server
				$.post(posturl,options.args,function(data,textStatus,xhr){
					// Put all data in static form as provided by the server
					// Flatten data so the each() loop works
					var flattened=flatten_json(data);
					
					$.each(flattened,function(k,v) {
						
						if (typeof(server_to_local_name_map[k])!=='undefined') {
							var n=server_to_local_name_map[k];

							if (options.fields[n] && options.fields[n].postSuccess) {
								options.fields[n].postSuccess(n,v);
							}
							else
								$('#'+n).text(v);
						}
					});
				},'json');
			}
		})		
	});
}
})(jQuery);
