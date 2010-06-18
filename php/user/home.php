<?php
require_once('beercrush/beercrush.php');

function solr_query($params) {
	global $BC;
	$solr_cfg=$BC->oak->get_config_info()->solr;
	$args=array('wt=json');
	foreach ($params as $k=>$v) {
		$args[]=$k.'='.urlencode($v);
	}
	$url='http://'.$solr_cfg->nodes[rand()%count($solr_cfg->nodes)].$solr_cfg->url.'/select?'.join('&',$args);
	return json_decode(file_get_contents($url));
}

include('../header.php');
?>

<h1>Statistics</h1>

<?php
$answer=solr_query(array(
	'q' => 'doctype:user',
	'rows' => 0,
));

?>
<div>count of total members:<?=$answer->response->numFound?></div>

<h1>Member of the Day</h1>

requires picture, about me, and some min. # of reviews; display img, about me, and # of reviews

<h1>A Random Set of 10 Helpful Members</h1>

these are people who contribute some min # of edits or beer menu updates; display avatar, name, and about me (this seemed like the most useful thing to encourage and promote, rather than new members who never have any activity on the site anyways so what is the point.)

<?php
include('../footer.php');
?>

