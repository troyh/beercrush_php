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
		// Validate the fields as best we can...
		
		// Separate street(s), city, state, zip and country
		var address=$("#new_brewery_address").val();
		// Separate each line
		var lines=address.split(/\n/);
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
			if (!lines[lines.length-1].match(/^\s*([^,]+),\s*([a-zA-Z]+)\s+(\d+)(\s*([A-Z]+))?$/))
			{
				$('#new_brewery_error').text('Address is unrecognizable');
			}
			else
			{
				var city=RegExp.$1;
				var state=RegExp.$2;
				var zip=RegExp.$3;
				var country=RegExp.$5;
				
				$.post('/api/post.fcgi/new_brewery',{
					name:$('#new_brewery_name').val(),
					uri:$('#new_brewery_uri').val(),
					phone:$('#new_brewery_phone').val(),
					street: street[0],
					city:   city,
					state:  state,
					zip:    zip,
					country:country
					},
					function(xml, textStatus) { $.overlayClose(); },
					'xml');
			}
		}
		
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
			$.post('/api/post.fcgi/edit_brewery',{id: $('#brewery_name').attr('bl:brewery_id'),name:$(this).parent().siblings(0).children().filter('input').val()},function(){},'xml');
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
			$.post('/api/post.fcgi/edit_brewery',{id: $('#brewery_name').attr('bl:brewery_id'),uri:$(this).parent().siblings(0).children().filter('input').val()},function(){},'xml');
		});
		$('.cancel_button').click(function(){
			$(this).parent().parent().remove();
			$('#brewery_uri').show();
		});
		
	})

	$('#brewery_addr').click(function()
	{
		var original="";
		$(this).children().filter('div').each(function(i) {original+=$(this).html().replace(/\s+/,' ')+'\n'});
		var textarea='<div><textarea name="address" rows="4" cols="40">'+original+'</textarea></div>';
		var button='<div><input type="button" class="save_button" value="Save" /><input type="button" class="cancel_button" value="Cancel" /></div>';
		$(this).after('<div>'+textarea+button+'</div>').hide();
		
		$('.save_button').click(function(){
		});
		$('.cancel_button').click(function(){
			$(this).parent().parent().remove();
			$('#brewery_addr').show();
		});
		
	})
})