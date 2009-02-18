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
}
