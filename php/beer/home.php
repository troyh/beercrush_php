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

$styles=BeerCrush::api_doc($BC->oak,'/style/flatlist');
$flavors=BeerCrush::api_doc($BC->oak,'/flavor/flatlist');

include('../header.php');
?>

<h1>Statistics</h1>

<?php
$recent_beers=solr_query(array(
	'q' => 'doctype:beer',
	'sort' => 'ctime desc',
	'rows' => 20
));
// print_r($recent_beers);
?>
<div>Beers: <?=$recent_beers->response->numFound?></div>

<?php
$results=solr_query(array(
	'q' => 'doctype:review AND beer_id:*',
	'rows' => 0
));
?>
<div>Beer reviews: <?=$results->response->numFound?></div>

<h1>20 new beers added to the db</h1>

<?php foreach ($recent_beers->response->docs as $beer):
	$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($beer->id)));
?>
	<div>
		<a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a>
		<a href="/<?=BeerCrush::docid_to_docurl($beer->id)?>"><?=$beer->name?></a> 
	</div>
<?php endforeach; ?>

<h1>a random beer style for the day</h1>

<?php
$style_ids=array();
foreach ($styles as $k=>$v) {
	$style_ids[]=$k;
}
$random_style=$style_ids[rand()%count($style_ids)];
?>
<h2>
	<div><a href="/style/<?=$styles->$random_style->id?>"><?=$styles->$random_style->name?></a></div>
</h2>

<?php
// Get a list of highest-rated beers in this beer's (first) style
$response=solr_query(array(
	'fl' => 'id,name,avgrating',
	'start'=> 0,
	'rows'=>5,
	'sort'=>'avgrating desc',
	'wt'=>'json',
	'q'=>'style:'.$random_style
));
?>

<ul>
	<?php foreach ($response->response->docs as $doc):
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->id)));
	?>
		<li><a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>"><?=$doc->name?></a> by <a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a> (<?=$doc->avgrating?>)</li>
	<?php endforeach;?>
</ul>


<h1>a random beer review</h1>

<?php
$results=solr_query(array(
	'q' => 'doctype:review AND beer_id:* AND rating:5',
	'sort' => 'random_'.rand().' asc',
	'rows' => 1
));
$review=$results->response->docs[0];
$reviewer=BeerCrush::api_doc($BC->oak,'user/'.$review->user_id);
?>

<div>
	<a href="/<?=BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($review->beer_id))?>" class="breweryname"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($review->beer_id)))->name?></a>
	<a href="/<?=BeerCrush::docid_to_docurl($review->beer_id)?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($review->beer_id))->name?></a>
	<div class="star_rating" title="Rating: <?=$review->rating?> of 5"><div class="avgrating" style="width: <?=$review->rating/5*100?>%"></div></div>
	<?php if (!empty($review->comments)):?><div class="comments"><?=$review->comments?></div><?php endif;?>
	<div class="by">&ndash; <?php if (!empty($reviewer->avatar)):?><img src="<?=$reviewer->avatar?>" /><?php endif;?><?=$reviewer->name?></div>
</div>

<h1>a random beer of the day</h1>

<?php
$results=solr_query(array(
	'q' => 'doctype:beer',
	'sort' => 'random_'.rand().' asc',
	'rows' => 1
));
$beer=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($results->response->docs[0]->id));

// Get flavor names
$flavor_names=array();
foreach ($beer->review_summary->flavors as $flavor) {
	$flavor_names[]=$flavors->{$flavor}->title;
}
?>

<div>
	<a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a>
	<a href="/<?=BeerCrush::docid_to_docurl($beer->id)?>"><?=$beer->name?></a>
	
	<?php if ($beer->photos->total && $beer->photos->thumbnail):?><div><img src="<?=$beer->photos->thumbnail?>" /></div><?php endif;?>
	<div class="star_rating" title="Rating: <?=$beer->review_summary->avg?> of 5"><div class="avgrating" style="width: <?=$beer->review_summary->avg/5*100?>%"></div></div>
	<div>Description:<?=$beer->description?></div>
	<div>Flavors:<?=join(', ',$flavor_names)?></div>
	<div>Body:<?=$beer->review_summary->body_avg?></div>
	<div>Balance:<?=$beer->review_summary->balance_avg?></div>
	<div>Aftertaste:<?=$beer->review_summary->aftertaste_avg?></div>
	<div>Style:<?=$styles->{$beer->styles[0]}->name?></div>
</div>

<?php
include('../footer.php');
?>