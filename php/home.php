<?php
require_once('beercrush/beercrush.php');

$oak=new OAK;

$flavors   =BeerCrush::api_doc($oak,'flavors');

// Build flavor id lookup table
$flavor_lookup=array();
build_flavor_lookup_table($flavors->flavors);
function build_flavor_lookup_table($flavors)
{
	global $flavor_lookup;
	
	foreach ($flavors as $flavor)
	{
		if (isset($flavor->flavors))
		{
			build_flavor_lookup_table($flavor->flavors);
		}
		else
		{
			$flavor_lookup[$flavor->id]=$flavor->title;
		}
	}
}

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
<div id="m">

<div style="height: 300px; background: grey;">Intro to site here
<h1>Count of Stuff</h1>

<?php $response=solr_query('doctype:beer',array('rows'=>0,));?>
<div><?=$response->response->numFound?> Beers</div>

<?php $response=solr_query('doctype:place',array('rows'=>0,));?>
<div><?=$response->response->numFound?> Places</div>

<?php $response=solr_query('doctype:brewery',array('rows'=>0,));?>
<div><?=$response->response->numFound?> Breweries</div>


</div>
<h2>Recently Enjoyed</h2>

<ul>
<?php $response=solr_query('doctype:review AND beer_id:beer* AND rating:[4 TO 5]',array(
	'rows'=>5,
	'sort'=>'ctime desc'
));
foreach ($response->response->docs as $doc):
	$review=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
	$reviewer=BeerCrush::api_doc($BC->oak,'/user/'.$review->user_id);
?>
	<li>
		<a href="/<?=BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($review->beer_id))?>" class="breweryname"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($review->beer_id)))->name?></a>
		<a href="/<?=BeerCrush::docid_to_docurl($review->beer_id)?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($review->beer_id))->name?></a>
		<div class="star_rating" title="Rating: <?=$review->rating?> of 5"><div class="avgrating" style="width: <?=$review->rating/5*100?>%"></div></div>
		<?php if (!empty($review->comments)):?><div class="comments"><?=$review->comments?></div><?php endif;?>
		<div class="by">&ndash; <?php if (!empty($reviewer->avatar)):?><img src="<?=$reviewer->avatar?>" /><?php endif;?><?=$reviewer->name?></div>
	</li>
<?php endforeach; ?>
</ul>

<h2>In Your Area</h2>
<div style="height: 300px; background: #FDFFC1;">
put place reviews here using geolocation if we have it, and if not, zip <input type="text" width="5"><button type="submit" value="go">go</button>
</div>

</div>
<div id="m_right_300">

<h2>Beer Review of the Day</h2>
<?php $response=solr_query('doctype:review AND beer_id:beer* AND rating:5',array(
	'rows'=>1,
	'sort'=>'random_'.rand().' asc',
));
$review=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($response->response->docs[0]->id));
$reviewer=BeerCrush::api_doc($BC->oak,'/user/'.$review->user_id);
?>
<div class="areview">
	<div class="type">
		<div class="beer"></div>
		<a href="/<?=BeerCrush::docid_to_docurl($doc->beer_id)?>" class="brewery"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->beer_id))->name?></a>
		<a href="/<?=BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->beer_id))?>"><?=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->beer_id)))->name?></a>
	
		<span class="user"><?php if (!empty($reviewer->avatar)):?><img src="<?=$reviewer->avatar?>" style="width:30px" /><?php endif;?><?=empty($reviewer->name)?"Anonymous":$reviewer->name?> posted <span class="datestring"><?=date('D, d M Y H:i:s O',$review->date_drank)?></span></span>
		<div class="triangle-border top">
			<div class="star_rating"><div id="avgrating" style="width: <?=$review->rating/5*100?>%"></div></div>
			<?if (!empty($review->comments)):?><div class="comments"><?=$review->comments?></div><?endif?>
				<div><?php
					$flavor_titles=array();
					if (isset($review->flavors))
					{
						foreach ($review->flavors as $flavor){$flavor_titles[]=$flavor_lookup[$flavor];}
					}
					print join(', ',$flavor_titles);
				?></div>
			<div id="ratings_section" class="cf">
				<div class="body" title="Body: <?=$review->body?> of 5"><div class="meter"><div style="width: <?=$review->body/5*100?>%"></div></div></div>
				<div class="balance" title="Balance: <?=$review->balance?> of 5"><div class="meter"><div style="width: <?=$review->balance/5*100?>%"></div></div></div>
				<div class="aftertaste" title="Aftertaste: <?=$review->aftertaste?> of 5"><div class="meter"><div style="width: <?=$review->aftertaste/5*100?>%"></div></div></div>
			</div>
		</div>
	</div>
</div>

<h2>Beer Crush News</h2>
<?php
$rss=simplexml_load_file("http://blog.beercrush.com/feed/");
// $namespaces=$rss->getDocNamespaces();
$rss->registerXPathNamespace('content','http://purl.org/rss/1.0/modules/content/');
$items=$rss->xpath('/rss/channel/item[position() < 3]');
foreach ($items as $post):
?>
	<div>
		<h4><a href="<?=$post->link?>"><?=$post->title?></a></h4>
		posted <span class="datestring"><?=date('D, d M Y H:i:s O',$post->pubDate)?><?=$post->pubDate?></span>
		<div class="comments"><?=$post->description?></div>
	</div>
<?php endforeach;?>

<h2>Crushworthy Place</h2>

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

</div>
<?php
include('footer.php');
?>