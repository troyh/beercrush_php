<?php
require_once('beercrush/beercrush.php');

$oak=new OAK;
$userdoc=BeerCrush::api_doc($oak,'user/'.$_GET['user_id']);

include("../header.php");
?>

<div id="user">
	<h1 id="user_name"><?=empty($userdoc->name)?"Anonymous":$userdoc->name?></h1>

	<div>Joined <span class="datestring"><?=date(BeerCrush::DATE_FORMAT,$userdoc->meta->timestamp)?></span></div>
	
	<div id="avatar">
	<img src="<?=empty($userdoc->avatar)?"/img/default_avatar.gif":$userdoc->avatar?>" />
	</div>

	<input type="hidden" id="user_id" value="<?=$userdoc->id?>" />

	<h2>About Me</h2>
	<div id="user_aboutme"><?=$userdoc->aboutme?></div>

	<div id="editable_save_msg"></div>
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />

</div>

<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function useGravatar()
{
	if (userinfo) {
		// See if their Gravatar really exists
		$.get(userinfo.gravatar_url,
			null,
			function(data,status,xhr){
				// The Gravatar does exist, use it
				$.post('/api/user/edit',
					{"user_id": userinfo.userid, "avatar": userinfo.gravatar_url},
					function(data,status,xhr) {
						$('#avatar img').attr('src',userinfo.gravatar_url);
					},
					'json'
				);
			}
		);
	}
}

var userinfo=null;

function pageMain()
{
	// If I'm logged in, see if this page is mine
	if ('user:'+$.cookie('userid')==$('#user_id').val()) {
		// It's me (I think), let's request my userdoc and see if the server gives it to me.
		$.getJSON('/api/user/fullinfo',function(data,status) {
			// This is my page
			userinfo=data;
			makeDocEditable('#user','user_id','/api/user/edit');
			if (userinfo.gravatar_url)
				$('#avatar').append('I have a <a href="http://en.gravatar.com/">Gravatar</a>. <input type="button" value="Use my Gravatar" onclick="useGravatar();" />');
		});
	}

}

</script>
<?php

include("../footer.php");

?>