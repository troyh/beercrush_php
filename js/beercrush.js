
var BeerCrush = {
	login_data: null,
	
	get_user_avatar: function() {
		if (this.login_data==null) {
			this.login_data=$.parseJSON($.cookie('login_data'));
		}
		return this.login_data.avatar;
	},
	get_user_name: function() {
		if (this.login_data==null) {
			this.login_data=$.parseJSON($.cookie('login_data'));
		}
		return this.login_data.name;
	},
	get_user_email: function() {
		if (this.login_data==null) {
			this.login_data=$.parseJSON($.cookie('login_data'));
		}
		return this.login_data.email;
	},
	get_user_md5: function() {
		if (this.login_data==null) {
			this.login_data=$.parseJSON($.cookie('login_data'));
		}
		return this.login_data.md5;
	},
	get_user_id: function() {
		var user_id=$.cookie('userid');
		if (typeof(user_id)=='undefined')
			return null;
		return user_id;
	},
	showusername: function() {
		$('#login').html((this.get_user_avatar()?'<img src="'+this.get_user_avatar()+'" />':'')+'Cheers, <a href="/user/'+$.cookie('userid')+'">'+this.get_user_name()+'</a>! <a href="javascript:BeerCrush.logout();">Logout</a>');
	},
	showlogin: function() {
		$('#login').html('<div><a href="">Login</a> or <a href="">Create Account</a></div>');

		var BC=this;
		$('#login a').click(function() {
			BC.show_login_dialog('Login');
			return false;
		});
	},
	show_login_dialog: function(title,success_func) {

		var BC=this;
		
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
				var md5_password=MD5(password);
				BC.login(email,md5_password,function(data) {
					$('#inplacelogin').dialog('close');

					data.email=email;
					data.md5=md5_password;
					BC.set_login_cookies(data,parseInt($('#inplacelogin input:checkbox[name=login_days]').val()));
					BC.showusername();

					if (success_func)
						success_func();
				},
				function(status) {
					switch (status) {
						case 403:
							$('#login_dialog_msg').addClass('ui-state-error');
							$('#login_dialog_msg').show('blind','slow');
							$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>Hmm, that combination doesn\'t match our files.  <a href="" onclick="BeerCrush.forgot_password(event);return false;">Forgot your password</a>?');
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
				var md5_password=MD5(password);
				BC.create_account(email,md5_password,function(){
					// Automatically log the user in
					BC.login(email,md5_password);
				},function(status){
					switch (status) {
						case 409: // Email already registered...
							// Maybe they really meant to login? Try that.
							BC.login(email,md5_password,function(data) {
								$('#inplacelogin').dialog('close');

								BC.set_login_cookies(data,parseInt($('#inplacelogin input:checkbox[name=login_days]').val()));
								BC.showusername();

								if (success_func)
									success_func();
							},function(status) {
								$('#login_dialog_msg').html('<span class="ui-icon ui-icon-alert"></span>That email address is already registered. <a href="" onclick="BeerCrush.forgot_password(event);return false;">Forgot your password</a>?');
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
	},
	set_login_cookies: function(login,expiry_days) {
		// Figure out how long to keep the cookies (session-only or a number of days)
		var date=null;
		if ($('#login_form input:checkbox[name=login_days]:checked').val()) {
			date = new Date();
			date.setTime(date.getTime() + ($('#login_form input:checkbox[name=login_days]').val() * 24 * 60 * 60 * 1000)); // set to expire in 1 day

			// Add login_days to data so we track it in a cookie
			data.login_days=expiry_days;
		}

		$.each(login,function(key,val) {
			switch (key) {
				case 'userid':
				case 'usrkey':
					$.cookie(key,val, { path: '/', expires: date});
					break;
				default:
					break;
			}
		});
		$.cookie('login_data',JSON.stringify(login), { path: '/', expires: date});
	},
	create_account: function(email,md5,success_func,fail_func) {
		$.ajax({
			type: 'POST',
			url: '/api/createlogin',
			data: {
				email: email,
				md5: md5
			},
			success: success_func,
			error: function(xhr,status,err) {
				if (fail_func)
					fail_func(xhr.status);
			},
			dataType: 'json'
		});
	},
	login: function(email,md5,success_func,fail_func) {
		$.ajax({
			type: 'POST',
			url: '/api/login',
			data: {
				email:email, 
				md5:md5
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
	},
	logout: function() {
		// Clear login cookies
		$.cookie('userid',null,{path:'/'});
		$.cookie('usrkey',null,{path:'/'});
		$.cookie('login_data',null,{path:'/'});

		this.showlogin();
	},
	forgot_password: function(evt) {
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
	},
	getJSON: function(options) {

		var BC=this;
		var original_error_func=options.error;
			
		options.dataType='json';
		options.error=function(xhr,textStatus,err) {
			if (xhr.status==403) { // Must login first
				if (BC.get_user_email() && BC.get_user_md5()) {
					BC.login(
						BC.get_user_email(),
						BC.get_user_md5(),
						function(data) {
							// Login worked, save login cookies
							data.email=BC.get_user_email();
							data.md5=BC.get_user_md5();
							BC.set_login_cookies(data);
							// ...and retry the original request
							options.error=original_error_func;
							$.ajax(options);
						},
						function() { original_error_func(403); } // Failed
					);
				}
				else if (original_error_func) {
					original_error_func(403);
				}
			}
		};
		
		$.ajax(options);
	}
};

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
		if ($(this).width() < options.style.width) { // Expand the width of the element
			$(this).width(options.style.width);
		}
		if ($(this).height() < options.style.height) { // Expand the height of the element
			$(this).height(options.style.height);
		}
		
		// Calculate the position of the element
		var x=$(this).offset().left+(($(this).width()-options.style.width)/2);
		var y=$(this).offset().top+(($(this).height()-options.style.height)/2);
		
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
			
			// Call onedit, if one is provided
			if (options.startEditing) {
				options.startEditing();
			}
			
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

/**
*
*  MD5 (Message-Digest Algorithm)
*  http://www.webtoolkit.info/
*
**/
 
var MD5 = function (string) {
 
	function RotateLeft(lValue, iShiftBits) {
		return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
	}
 
	function AddUnsigned(lX,lY) {
		var lX4,lY4,lX8,lY8,lResult;
		lX8 = (lX & 0x80000000);
		lY8 = (lY & 0x80000000);
		lX4 = (lX & 0x40000000);
		lY4 = (lY & 0x40000000);
		lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
		if (lX4 & lY4) {
			return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
		}
		if (lX4 | lY4) {
			if (lResult & 0x40000000) {
				return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
			} else {
				return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
			}
		} else {
			return (lResult ^ lX8 ^ lY8);
		}
 	}
 
 	function F(x,y,z) { return (x & y) | ((~x) & z); }
 	function G(x,y,z) { return (x & z) | (y & (~z)); }
 	function H(x,y,z) { return (x ^ y ^ z); }
	function I(x,y,z) { return (y ^ (x | (~z))); }
 
	function FF(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function GG(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function HH(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function II(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function ConvertToWordArray(string) {
		var lWordCount;
		var lMessageLength = string.length;
		var lNumberOfWords_temp1=lMessageLength + 8;
		var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
		var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
		var lWordArray=Array(lNumberOfWords-1);
		var lBytePosition = 0;
		var lByteCount = 0;
		while ( lByteCount < lMessageLength ) {
			lWordCount = (lByteCount-(lByteCount % 4))/4;
			lBytePosition = (lByteCount % 4)*8;
			lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
			lByteCount++;
		}
		lWordCount = (lByteCount-(lByteCount % 4))/4;
		lBytePosition = (lByteCount % 4)*8;
		lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
		lWordArray[lNumberOfWords-2] = lMessageLength<<3;
		lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
		return lWordArray;
	};
 
	function WordToHex(lValue) {
		var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
		for (lCount = 0;lCount<=3;lCount++) {
			lByte = (lValue>>>(lCount*8)) & 255;
			WordToHexValue_temp = "0" + lByte.toString(16);
			WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
		}
		return WordToHexValue;
	};
 
	function Utf8Encode(string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
 
		for (var n = 0; n < string.length; n++) {
 
			var c = string.charCodeAt(n);
 
			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
 
		}
 
		return utftext;
	};
 
	var x=Array();
	var k,AA,BB,CC,DD,a,b,c,d;
	var S11=7, S12=12, S13=17, S14=22;
	var S21=5, S22=9 , S23=14, S24=20;
	var S31=4, S32=11, S33=16, S34=23;
	var S41=6, S42=10, S43=15, S44=21;
 
	string = Utf8Encode(string);
 
	x = ConvertToWordArray(string);
 
	a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
 
	for (k=0;k<x.length;k+=16) {
		AA=a; BB=b; CC=c; DD=d;
		a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
		d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
		c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
		b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
		a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
		d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
		c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
		b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
		a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
		d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
		c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
		b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
		a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
		d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
		c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
		b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
		a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
		d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
		c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
		b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
		a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
		d=GG(d,a,b,c,x[k+10],S22,0x2441453);
		c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
		b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
		a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
		d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
		c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
		b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
		a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
		d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
		c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
		b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
		a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
		d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
		c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
		b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
		a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
		d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
		c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
		b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
		a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
		d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
		c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
		b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
		a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
		d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
		c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
		b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
		a=II(a,b,c,d,x[k+0], S41,0xF4292244);
		d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
		c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
		b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
		a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
		d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
		c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
		b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
		a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
		d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
		c=II(c,d,a,b,x[k+6], S43,0xA3014314);
		b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
		a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
		d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
		c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
		b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
		a=AddUnsigned(a,AA);
		b=AddUnsigned(b,BB);
		c=AddUnsigned(c,CC);
		d=AddUnsigned(d,DD);
	}
 
	var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
 
	return temp.toLowerCase();
}