<?php
require_once('beercrush/beercrush.php');

$oak=new OAK;
$all_users=BeerCrush::api_doc($oak,'/users');
// print_r($users);exit;
include("../header.php");
?>

<h1>People</h1>

<?php
foreach ($all_users as $letter=>$users) 
{
	foreach ($users as $user) 
	{
		$userinfo=BeerCrush::api_doc($oak,str_replace(':','/',$user->id));
		// print "userinfpo=";print_r($userinfo);
?>
		<div>
			<div><img src="<?=empty($userinfo->avatar)?BeerCrush::DEFAULT_AVATAR_URL:$userinfo->avatar?>" /></div>
			<div><a href="/<?=str_replace(':','/',$user->id)?>"><?=$user->name?></a></div>
			<div class="datestring"><?=date(BeerCrush::DATE_FORMAT,$userinfo->meta->timestamp)?></div>
			<div><?=$userinfo->aboutme?></div>
		</div>
<?php
	}
}
?>

<script type="text/javascript">

function pageMain()
{
}

</script>
<?php

include("../footer.php");

?>