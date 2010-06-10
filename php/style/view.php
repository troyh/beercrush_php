<?php
require_once('beercrush/beercrush.php');

$styles=BeerCrush::api_doc($BC->oak,'beerstyles');
// print_r($styles);
$style=find_style($styles->styles,$_GET['style_id']);
// print_r($style);

function find_style($styles,$id) {
	foreach ($styles as $style) {
		if ($style->id==$id) {
			return $style;
			break;
		}
		if (isset($style->styles)) {
			$s=find_style($style->styles,$id);
			if (!is_null($s))
				return $s;
		}
	}
	return null;
}

include('../header.php');
?>
<a href="/style/">All Beer Styles</a>  &gt; <?=$style->name?>
<h1><?=$style->name?></h1>
<div class="cf"><div class="label">Type: </div><div><?=$style->type?></div></div>
<div class="cf"><div class="label">Origin: </div><div><?=$style->origin?></div></div>
<div class="cf"><div class="label">Original Gravity: </div><div><?=$style->OGlo?>-<?=$style->OGhi?></div></div>
<div class="cf"><div class="label">Final Gravity: </div><div><?=$style->FGlo?>-<?=$style->FGhi?></div></div>
<div class="cf"><div class="label">Bitterness: </div><div><?=$style->IBUlo?>-<?=$style->IBUhi?> IBUs</div></div>
<div class="cf"><div class="label">Color: </div><div><?=$style->SRMlo?>-<?=$style->SRMhi?> srm</div></div>
<div class="cf"><div class="label">Alcohol (abv): </div><div><?=$style->ABVlo?>-<?=$style->ABVhi?></div></div>

<h2>Top <?=$style->name?> Beers</h2>

<?php

// Get a list of highest-rated beers in this beer's (first) style
$solr_url='http://'.$BC->oak->get_config_info()->solr->nodes[rand()%count($BC->oak->get_config_info()->solr->nodes)].$BC->oak->get_config_info()->solr->url.'/select?';
$url=$solr_url.'fl=id,name,avgrating&start=0&rows=20&sort=avgrating+desc&wt=json&q=style:'.$style->id;
// print $url;exit;
$response=json_decode(@file_get_contents($url));
// print_r($response);exit;
?>

<ul>
	<?php foreach ($response->response->docs as $doc):
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->id)));
	?>
		<li><a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>"><?=$doc->name?></a> by <a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a> (<?=$doc->avgrating?>)</li>
	<?php endforeach;?>
</ul>

<?php

// Get a list of newest beers in this beer's (first) style
$url=$solr_url.'fl=id,name,ctime&start=0&rows=20&wt=json&sort=ctime+desc&q=style:'.$style->id;
// print $url;exit;
$response=json_decode(@file_get_contents($url));
// print_r($response);exit;

?>

<h2>Recently Added <?=$style->name?> Beers</h2>
<ul>
	<?php foreach ($response->response->docs as $doc):
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->id)));
	?>
		<li><a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>"><?=$doc->name?></a> by <a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a> [<?=$doc->ctime?>]</li>
	<?php endforeach;?>
</ul>

<?php
include('../footer.php');
?>

