
function breakUpAddress(txt)
{
	var parts={};
	
	// Separate street(s), city, state, zip and country
	// Separate each line
	var lines=jQuery.trim(txt).split(/\n/);
	if (lines.length<2) 
	{
		$('#new_brewery_error').text('Address must have at least 2 lines');
	}
	else 
	{
		var street=[];
		for (var i = 0; i < lines.length-1; i++)
		{
			street[i]=lines[i];
		}
		if (!lines[lines.length-1].match(/^\s*([^,]+),\s*([a-zA-Z]+)\s*,?\s+(\d+)(\s*([A-Z]+))?$/))
		{
			$('#new_brewery_error').text('Address is unrecognizable');
		}
		else
		{
			parts={
				'street'  : street,
				'city'    : RegExp.$1,
				'state'   : RegExp.$2,
				'zip'     : RegExp.$3,
				'country' : RegExp.$5
			};
		}
	}
	
	return parts;
}

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

	$('#new_brewery_save').click(function() {
		var address=$("#new_brewery_address").val();
		parts=breakUpAddress(address);
		
		// Validate the fields as best we can...
		
		$.post('/api/post.fcgi/new_brewery',{
			name:$('#new_brewery_name').val(),
			uri:$('#new_brewery_uri').val(),
			phone:$('#new_brewery_phone').val(),
			street: parts.street[0],
			city:   parts.city,
			state:  parts.state,
			zip:    parts.zip,
			country:parts.country
			},
			function(xml, textStatus) { $.overlayClose(); },
			'xml');
		
	});
	
	$('#new_brewery_cancel').click(function() {
		$.overlayClose();
	});
	
	
	$('#brewery_name').click(function()
	{
		var original=$(this).html();
		var textline='<div><input type="text" name="" value="'+original+'"></div>';
		var button='<div><input type="button" class="save_button" value="Save" /><input type="button" class="cancel_button" value="Cancel" /></div>';
		$(this).after('<div>'+textline+button+'</div>').hide();

		$('.save_button').click(function(){
			$.post('/api/post.fcgi/edit_brewery',{id: $('#brewery_name').attr('bl:brewery_id'),name:$(this).parent().siblings(0).children().filter('input').val()},function(content,textStatus){
				$('#brewery_name').text($("brewery > name",content).text());
			},'xml');
			$(this).parent().parent().remove();
			$('#brewery_name').show();
		});
		$('.cancel_button').click(function(){
			$(this).parent().parent().remove();
			$('#brewery_name').show();
		});
	})

	$('#brewery_uri').click(function()
	{
		var original=$(this).html();
		var textline='<div><input type="text" name="" value="'+original+'"></div>';
		var button='<div><input type="button" class="save_button" value="Save" /><input type="button" class="cancel_button" value="Cancel" /></div>';
		$(this).after('<div>'+textline+button+'</div>').hide();

		$('.save_button').click(function(){
			$.post('/api/post.fcgi/edit_brewery',{id: $('#brewery_name').attr('bl:brewery_id'),uri:$(this).parent().siblings(0).children().filter('input').val()},function(content,textStatus){
				$('#brewery_uri').text($("brewery > uri",content).text());
			},'xml');
			$(this).parent().parent().remove();
			$('#brewery_uri').show();
		});
		$('.cancel_button').click(function(){
			$(this).parent().parent().remove();
			$('#brewery_uri').show();
		});
		
	})

	$('#brewery_phone').click(function()
	{
		var original=$(this).html();
		var textline='<div><input type="text" name="" value="'+original+'"></div>';
		var button='<div><input type="button" class="save_button" value="Save" /><input type="button" class="cancel_button" value="Cancel" /></div>';
		$(this).after('<div>'+textline+button+'</div>').hide();

		$('.save_button').click(function(){
			$.post('/api/post.fcgi/edit_brewery',{id: $('#brewery_name').attr('bl:brewery_id'),phone:$(this).parent().siblings(0).children().filter('input').val()},function(content,textStatus){
				$('#brewery_phone').text($("brewery > phone",content).text());
			},'xml');
			$(this).parent().parent().remove();
			$('#brewery_phone').show();
		});
		$('.cancel_button').click(function(){
			$(this).parent().parent().remove();
			$('#brewery_phone').show();
		});
		
	})

	$('#brewery_addr').click(function()
	{
		var original="";
		$(this).children().filter('div').each(function(i) {original+=jQuery.trim($(this).html().replace(/[\r\n]/g,' ').replace(/\s+/g,' '))+'\n'});
		var textarea='<div><textarea id="inplaceedit_address" name="address" rows="4" cols="40">'+original+'</textarea></div>';
		var button='<div><input type="button" class="save_button" value="Save" /><input type="button" class="cancel_button" value="Cancel" /></div>';
		$(this).after('<div>'+textarea+button+'</div>').hide();
		
		$('.save_button').click(function(){
			var parts=breakUpAddress($('#inplaceedit_address').val());
			
			if (!parts)
			{
				console.log('Address is not recognizable');
			}
			else
			{
				parts.id=$('#brewery_name').attr('bl:brewery_id');
				$.post('/api/post.fcgi/edit_brewery',parts,function(content,textStatus){
					$('#brewery_addr').html(
						'<div>'
						+$("brewery > address > street",content).text()
						+'</div><div>'+$("brewery > address > city",content).text()
						+', '+$("brewery > address > state",content).text()
						+' '+$("brewery > address > zip",content).text()
						+' '+$("brewery > address > country",content).text()
						+'</div>'
					);
				},'xml');

				$(this).parent().parent().remove();
				$('#brewery_addr').show();
			}
		});
		$('.cancel_button').click(function(){
			$(this).parent().parent().remove();
			$('#brewery_addr').show();
		});
		
	})
})