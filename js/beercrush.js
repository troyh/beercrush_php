var login_data=new Object;

function get_user_id() {
	return $.cookie('userid');
}

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
	$('#login').html((login_data.avatar?'<img src="'+login_data.avatar+'" />':'')+'Cheers, <a href="/user/'+$.cookie('userid')+'">'+login_data.name+'</a>! <a href="javascript:logout();">Logout</a>');
}

function showlogin()
{
	$('#login').html('\
	<form id="login_form" method="post" action="/api/login">\
	<div id="form_login">\
		Sign in or <a href="" id="show_register">create an account</a>\
		<div>Email: <input name="email" type="text" size="10" />\
		Password: <input name="password" type="password" size="5" /></div>\
		<span id="login_dropdown" class="tiny"><input type="checkbox" name="login_days" value="1" />Remember me</span><input value="Sign In" type="submit" />\
	</div>\
	<div id="form_register" class="hidden">\
		Create an account or <a href="" onClick="$(\'#form_register\').hide();$(\'#form_login\').show();" id="show_login">sign in</a>\
		<div>Email: <input name="email" type="text" size="10" />\
		Password: <input name="password" type="password" size="5" /></div>\
		<input value="Create Account" type="submit" />\
	</div>\
	<span id="login_msg"></span>\
	</form>');

	$('#login_form').submit(function(){login();return false;});
	$('#show_register').click(function(){$('#form_login').addClass('hidden');$('#form_register').removeClass('hidden'); return false;});
	$('#show_login').click(function(){$('#form_register').addClass('hidden');$('#form_login').removeClass('hidden'); return false;});
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
