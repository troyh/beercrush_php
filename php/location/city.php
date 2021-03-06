<?php
require_once('beercrush/beercrush.php');

$locations=new stdClass;

$key=urlencode('["'.$_GET['country'].'","'.$_GET['state'].'","'.$_GET['city'].'"]');
$endkey=urlencode('["'.$_GET['country'].'","'.$_GET['state'].'","'.$_GET['city'].'\\\\uFFFF"]');
$view_url='location/all?group_level=3&inclusive_end=true&startkey='.$key.'&endkey='.$endkey;
// print $view_url;exit;
$BC->oak->get_view($view_url,&$locations);
// print_r($locations);exit;

include('../header.php');
?>

<a href="../../../">All</a> &gt; 
<a href="../../"><?=$locations->rows[0]->key[0]?></a> &gt;
<a href=".."><?=$locations->rows[0]->key[1]?></a> &gt;
<?=$locations->rows[0]->key[2]?>

<h1><?=$locations->rows[0]->key[2]?> Beer Places</h1>
Jump to <a href="#Brewpub">Brewpubs</a> | <a href="#Bar">Bars</a> | <a href="#Restaurant">Restaurants</a> | <a href="#Store">Stores</a> | <a href="#Brewery">Breweries</a>

<?php
$cfg=$BC->oak->get_config_info();
foreach (array('Brewpub','Bar','Restaurant','Store') as $placetype) :
	$solr_url='http://'.$cfg->solr->nodes[rand()%count($cfg->solr->nodes)].$cfg->solr->url.'/select?fl=id,name,avgrating&start=0&rows=20&sort=avgrating+desc&wt=json&q=doctype:place+AND+placetype:'.$placetype.'+AND+address_state:"'.urlencode($_GET['state']).'"+AND+address_city:"'.urlencode($_GET['city']).'"';
	$places=json_decode(file_get_contents($solr_url));
?>
<p><a name="<?=$placetype?>"></a></p>
<h2><?=$places->response->numFound?> <?=$placetype?>s</h2>
<ul>
<?php
	foreach($places->response->docs as $doc):
		$place=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
?>
	<li>
		<a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>"><?=$doc->name?></a>
		<span><?=$doc->avgrating?></span>
	</li>
	<?php endforeach;?>
</ul>
<?php
endforeach;

$solr_url='http://'.$cfg->solr->nodes[rand()%count($cfg->solr->nodes)].$cfg->solr->url.'/select?fl=id,name&start=0&rows=20&sort=name+asc&wt=json&q=doctype:brewery+AND+address_state:"'.urlencode($_GET['state']).'"+AND+address_city:"'.urlencode($_GET['city']).'"';
$breweries=json_decode(file_get_contents($solr_url));
?>
<p><a name="Brewery"></a></p>
<h2><?=$breweries->response->numFound?> Breweries</h2>
<ul>
<?php
	foreach($breweries->response->docs as $doc):
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
?>
	<li>
		<a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>"><?=$doc->name?></a>
	</li>
	<?php endforeach;?>
</ul>

<?php
include('../footer.php');
?>