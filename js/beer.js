function editingOn(ctl,multiline,ajaxURI,docid,fieldname,result_xpath,func)
{
	var ctlid='edit_'+docid.replace(/[^a-zA-Z0-9]/g,'_')+'_'+fieldname;
	var divid='div_'+docid.replace(/[^a-zA-Z0-9]/g,'_')+'_'+fieldname;
	
	var textline;
	if (multiline)
	{
		textline='<div><textarea style="width:'+$(ctl).width()+'px;height:'+($(ctl).height()*1.2)+'px;font-size:'+$(ctl).css('font-size')+';" id="'+ctlid+'" name="'+fieldname+'" value="'+$(ctl).text()+'"></textarea></div>';
	}
	else
	{
		textline='<div><input style="width:'+$(ctl).width()+'px;height:'+($(ctl).height()*1.2)+'px;font-size:'+$(ctl).css('font-size')+';" id="'+ctlid+'" type="text" name="'+fieldname+'" value="'+$(ctl).text()+'"></div>';
	}
	var buttons='<div><input type="button" class="save_button" value="Save" /><input type="button" class="cancel_button" value="Cancel" /></div>';
	
	$(ctl).after('<div id="'+divid+'">'+textline+buttons+'</div>').hide();
	
	// Call user function 
	if (func)
		func($('#'+ctlid));

	$('.save_button').click(function(){
		var obj=new Object();

		// Because jQuery idiotically cannot figure out namespaces in selectors with attributes,
		// I have to separate the element and the attribute!
		if (docid.match(/(.*)\[(.*)\]/))
		{
			docid_elem=RegExp.$1;
			docid_attr=RegExp.$2;
			obj['id']=$(docid_elem).attr(docid_attr);
		}
		else
			obj['id']=$(docid).val();
		
		obj[$('#'+ctlid).attr('name')]=$('#'+ctlid).val();
		
		$.post(ajaxURI,
			obj,
			function(content,textStatus)
			{
				$(ctl).text($(result_xpath,content).text());
			},
			'xml');
			
		$('#'+divid).remove();
		$(ctl).show();
	});
	$('.cancel_button').click(function(){
		$('#'+divid).remove();
		$(ctl).show();
	});
}

$(document).ready(function()
{
	$('#beer_name').click(function()		{editingOn($(this),false,'/api/post.fcgi/edit_beer','#beer_name[bl:beer_id]','name','beer > name');});
	$('#beer_brewer').click(
		function()		
		{
			editingOn(
				$(this),
				false,
				'/api/post.fcgi/edit_beer',
				'#beer_name[bl:beer_id]',
				'brewery_name',
				'beer[brewery_id]',
				function(ctl) 
				{
					ctl.autocomplete('/api/autocomplete.fcgi',
					{
						width:'200px',
						mustMatch: true,
						autoFill: true
					});
				}
			);
		}
	);
	$('#beer_descrip').click(function()		{editingOn($(this),true ,'/api/post.fcgi/edit_beer','#beer_name[bl:beer_id]','description','beer > description');});
	$('#beer_abv').click(function()			{editingOn($(this),false,'/api/post.fcgi/edit_beer','#beer_name[bl:beer_id]','abv','beer > abv');});
	$('#beer_bjcp_style').click(
		function()		
		{
			editingOn(
				$(this),
				false,
				'/api/post.fcgi/edit_beer',
				'#beer_name[bl:beer_id]',
				'bjcp_style_name',
				'beer[bjcp_style_id]',
				function(ctl) 
				{
					ctl.autocomplete('/api/autocomplete.fcgi',
						{
							extraParams:{dataset:"bjcp_style"},
							width:'200px',
							mustMatch: true,
							autoFill: true
						}
					);
				}
			);
		}
	);

})
