<?php
require_once('beercrush/beercrush.php');
include('header.php');

function solr_query($q,$options=null) {
	global $BC;
	$cfg=$BC->oak->get_config_info();
	$url='http://'.$cfg->solr->nodes[rand()%count($cfg->solr->nodes)].$cfg->solr->url.'/select?';
	
	// Add options
	if (is_null($options))
		$options=array();

	if (!isset($options['wt']))
		$options['wt']='json'; // JSON output
	
	$params=array();
	foreach ($options as $k=>$v) {
		$params[]=$k.'='.urlencode($v);
	}
	$url.='q='.urlencode($q).'&'.join('&',$params);

	return json_decode(file_get_contents($url));
}
?>

<h1>Recently Enjoyed</h1>

<ul>
<?php $response=solr_query('doctype:review AND beer_id:beer* AND rating:[4 TO 5]',array(
	'rows'=>10,
	'sort'=>'ctime desc'
));
foreach ($response->response->docs as $doc):
	$review=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
	$reviewer=BeerCrush::api_doc($BC->oak,'/user/'.$review->user_id);
?>
	<li>
		<a href="/<?=BeerCrush::docid_to_docurl($review->beer_id)?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($review->beer_id))->name?></a> by 
		<a href="/<?=BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($review->beer_id))?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($review->beer_id)))->name?></a>
		<div>Rating:<?=$review->rating?></div>
		<div>Comments:<?=$review->comments?></div>
		<div><?php if (!empty($reviewer->avatar)):?><img src="<?=$reviewer->avatar?>" /><?php endif;?><?=$reviewer->name?></div>
	</li>
<?php endforeach; ?>
</ul>


<h1>In Your Area</h1>
<h1>Our blog news</h1>

<?php
$rss=simplexml_load_file("http://blog.beercrush.com/feed/");
// $namespaces=$rss->getDocNamespaces();
$rss->registerXPathNamespace('content','http://purl.org/rss/1.0/modules/content/');
$items=$rss->xpath('/rss/channel/item[position() < 3]');
foreach ($items as $post):
?>
	<div>
		<div><a href="<?=$post->link?>"><?=$post->title?></a> <?=$post->pubDate?></div>
		<div><?=$post->description?></div>
	</div>
<?php endforeach;?>
<h1>A Random 5-star Beer Review</h1>

<div>
<?php $response=solr_query('doctype:review AND beer_id:beer* AND rating:5',array(
	'rows'=>1,
	'sort'=>'random_'.rand().' asc',
));
$review=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($response->response->docs[0]->id));
$reviewer=BeerCrush::api_doc($BC->oak,'/user/'.$review->user_id);
?>
	<div><a href="/<?=BeerCrush::docid_to_docurl($doc->beer_id)?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->beer_id))->name?></a></div>
	<div><a href="/<?=BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->beer_id))?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->beer_id)))->name?></a></div>
	<div>Date Drank:<?=$review->date_drank?></div>
	<div>Rating:<?=$review->rating?></div>
	<div>Body:<?=$review->body?></div>
	<div>Balance:<?=$review->balance?></div>
	<div>Aftertaste:<?=$review->aftertaste?></div>
	<div>Price: $<?=$review->purchase_price?> <?=$review->poured_from?> at 
		<a href="/<?=BeerCrush::docid_to_docurl($review->purchase_place_id)?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($review->purchase_place_id))->name?></a></div>
	<div>Flavors:<?=join(',',$review->flavors)?></div>
	<div><?php if (!empty($reviewer->avatar)):?><img src="<?=$reviewer->avatar?>" /><?php endif;?><?=$reviewer->name?></div>
</div>

<h1>A Random Place</h1>

<?php
// TODO: only get a place with a high crushworthy score
$response=solr_query('doctype:place',array(
	'rows'=>1,
	'sort'=>'random_'.rand().' asc',
));
$place=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($response->response->docs[0]->id));
$menu=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($response->response->docs[0]->id).'/menu');
?>

<?php if ($place->photos->total):?><img src="<?=$place->photos->thumbnail?>" /><?php endif;?>
<div><a href="/<?=BeerCrush::docid_to_docurl($place->id)?>"><?=$place->name?></a></div>
<div><?=$place->placetype?></div>
<div><?=$place->address->city?>, <?=$place->address->state?> <?=$place->address->country?></div>
<div>Crushworthy Score: <?=$place->crushworthy_index?></div>
<div>Avg Rating: <?=$place->review_summary->avg?></div>
<div>Avg Atmosphere Rating: <?=$place->review_summary->atmosphere_avg?></div>
<div>Avg Service Rating: <?=$place->review_summary->service_avg?></div>
<div>Avg Food Rating: <?=$place->review_summary->food_avg?></div>
<div># beers on menu: <?=count($menu->items)?></div>

<h1>Count of Stuff</h1>

<?php $response=solr_query('doctype:beer',array('rows'=>0,));?>
<div><?=$response->response->numFound?> Beers</div>

<?php $response=solr_query('doctype:place',array('rows'=>0,));?>
<div><?=$response->response->numFound?> Places</div>

<?php $response=solr_query('doctype:brewery',array('rows'=>0,));?>
<div><?=$response->response->numFound?> Breweries</div>

<?php
include('footer.php');
?>