var flavors_selected={};

function set_flavors_value(f) {
	var flavors_arr=[];
	$.each(f,function(k,v){
		if (v)
			flavors_arr.push(k.replace(/^flavor-/,''));
	});
	$('#beer_review_form input[name=flavors]').val(flavors_arr.join(' '));
}

$('#beer_review_form').load('/beer/reviewform',null,function() {
	$.getScript("/js/jquery.ui.stars.js",function(){
		$('#star-rating').stars();
		$('#beer_review_form input[name=beer_id]').val($('#beer_id').val());
	
		$('#beer_review_form').dialog({
			title: 'Post a review',
			modal:true,
			resizable: false,
			open: function() {

				$('#body-slider').slider({
					min:0,
					max:5,
					step:1,
					change: function(event,ui) {
						$('#beer_review_form input[name=body]').val(ui.value);
					}
				});
				$('#balance-slider').slider({
					min:0,
					max:5,
					step:1,
					change: function(event,ui) {
						$('#beer_review_form input[name=balance]').val(ui.value);
					}
				});
				$('#aftertaste-slider').slider({
					min:0,
					max:5,
					step:1,
					change: function(event,ui) {
						$('#beer_review_form input[name=aftertaste]').val(ui.value);
					}
				});
				$('input[name=purchase_place_name]','#beer_review_form').autocomplete({
					source: function(req,callback) {
						$.get('/api/autocomplete.fcgi?dataset=places&q='+req.term,function(data){
							// Time and split the data into lines
							callback($.trim(data).split('\n'));
						});
					},
					select: function(event,ui) {
						$('#beer_review_form input[name=purchase_place_id]').val('');
						$.getJSON('/api/search?q='+ui.item.value+'&doctype=place',function(data,textStatus){
							$('#beer_review_form input[name=purchase_place_id]').val(data.response.docs[0].id);
						});
					}
				});
				$('#beer_review_form input[name=date_drank]').datepicker({
					maxDate: "+0"
				});
				$('#flavors-accordion').accordion({
					active: false,
					collapsible: true
				});
				$('#flavors-accordion ul').selectable({
					selected: function(event,ui) {
						flavors_selected[ui.selected.id]=1;
						set_flavors_value(flavors_selected);
					},
					unselected: function(event,ui) {
						flavors_selected[ui.unselected.id]=0;
						set_flavors_value(flavors_selected);
					}
				});
			
				// If there's an existing review, fill out the fields
				if (get_user_id()!=null && $('#beer_id').val().trim().length) {
					$.getJSON('/api/review/'+$('#beer_id').val().replace(/:/g,'/')+'/'+get_user_id(),
						null,
						function(data){
							console.log(data);
							$('#aftertaste-slider').slider('value',data.aftertaste);
							$('#balance-slider').slider('value',data.balance);
							$('#body-slider').slider('value',data.body);
							$('#beer_review_form textarea[name=comments]').val(data.comments);
							$('#beer_review_form input[name=date_drank]').val(data.date_drank);
							$.each(data.flavors,function(idx,val){
								$('#flavor-'+val).addClass('ui-selected');
							});
							$('#beer_review_form input[name=poured_from][value='+data.poured_from+']').attr('checked','checked');
							$.getJSON('/api/'+data.purchase_place_id.replace(/:/g,'/'),null,function(data){
								$('#beer_review_form input[name=purchase_place_id]').val(data.id);
								$('#beer_review_form input[name=purchase_place_name]').val(data.name);
							});
							$('#beer_review_form input[name=purchase_price]').val(data.purchase_price?data.purchase_price:'');
							$('#beer_review_form input[name=rating]').val(data.rating);
							$('#star-rating').stars('select',data.rating);
						}
					);
				}
			
				$('#post_review_button').click(function(){
					$('review_result_msg').hide();
					$.ajax({
						url: '/api/beer/review',
						data: $('#review_form').serializeArray(),
						dataType: 'json',
						success: function(data,textStatus,xhr){
							$('#beer_review_form').dialog('close');
						},
						error: function(xhr,textStatus,err) {
							$('#review_result_msg').html('Failed to post review').show();
						}
					});
				});
			}
		});
	});
});
