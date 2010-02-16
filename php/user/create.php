<?php
require_once('beercrush/oak.class.php');

include("../header.php");
?>

<h1>Create an account</h1>

<div>
	<form id="new_user_form" method="post" action="/api/createlogin">
		<div>
			Email: <input type="text" name="email" value="" />
		</div>
		<div>
			Password: <input type="text" name="password" value="" />
		</div>
		<input type="submit" value="Create Account" />
	</form>
	<div id="new_user_msg"></div>
</div>
	
<script type="text/javascript">

function pageMain()
{
	$('#new_user_form').submit(function(){
		
		$('#new_user_form').ajaxError(function(evt,xhr,options,exception) {
			if (options.url==$('#new_user_form').attr('action')) {
				var rsp=jQuery.parseJSON(xhr.responseText);
				$('#new_user_msg').html('Unable to create account: '+rsp.reason);
			}
		});

		$('#new_user_msg').text('');
		$.post($('#new_user_form').attr('action'),
			$('#new_user_form').serialize(),
			function(data) {
				$('#new_user_msg').html('Account created');
			},
			'json');
			
		return false;
	});
	
}

</script>
<?php

include("../footer.php");

?>