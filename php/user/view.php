<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$userdoc=json_decode(file_get_contents($oak->get_config_info()->api->base_uri.'/user/'.$_GET['user_id']));
// print_r($userdoc);exit;

include("../header.php");
?>

<div id="user">
	<h1 id="user_name"><?=empty($userdoc->name)?"Anonymous":$userdoc->name?></h1>
	
	<img src="<?=empty($userdoc->avatar)?"/img/default_avatar.gif":$userdoc->avatar?>" />

	<input type="hidden" id="user_id" value="<?=$userdoc->id?>" />
	
	<div>Joined <span class="datestring"><?=date('D, d M Y H:i:s O',$userdoc->meta->timestamp)?></span></div>

	<h2>About Me</h2>
	<div id="user_aboutme"><?=$userdoc->aboutme?></div>

	<div id="editable_save_msg"></div>
	<input class="editable_savechanges_button hidden" type="button" value="Save Changes" />
	<input class="editable_cancelchanges_button hidden" type="button" value="Discard Changes" />

</div>

<script type="text/javascript" src="/js/jquery.jeditable.mini.js"></script>
<script type="text/javascript">

function pageMain()
{
	// If I'm logged in, see if this page is mine
	if ('user:'+$.cookie('userid')==$('#user_id').val()) {
		// It's me (I think), let's request my userdoc and see if the server gives it to me.
		$.getJSON('/api/user/fullinfo',function(data,status) {
			makeDocEditable('#user','user_id','/api/user/edit');
		});
	}

}

</script>
<?php

include("../footer.php");

?>