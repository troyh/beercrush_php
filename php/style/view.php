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
<a href="/style/">All Styles</a>
<h1><?=$style->name?></h1>

<div>OG:<?=$style->OGlo?>-<?=$style->OGhi?></div>
<div>FG:<?=$style->FGlo?>-<?=$style->FGhi?></div>
<div>IBU:<?=$style->IBUlo?>-<?=$style->IBUhi?></div>
<div>SRM:<?=$style->SRMlo?>-<?=$style->SRMhi?></div>
<div>ABV:<?=$style->ABVlo?>-<?=$style->ABVhi?></div>
<div>Type:<?=$style->type?></div>
<div>Origin:<?=$style->origin?></div>
<div>From:<?=$style->from?></div>

<h2>Beers</h2>

<?php

// Get a list of highest-rated beers in this beer's (first) style
$solr_url='http://'.$BC->oak->get_config_info()->solr->nodes[rand()%count($BC->oak->get_config_info()->solr->nodes)].$BC->oak->get_config_info()->solr->url.'/select?';
$url=$solr_url.'fl=id,name&start=0&rows=20&wt=json&q=style:'.$style->id;
// print $url;exit;
$response=json_decode(@file_get_contents($url));
// print_r($response);exit;
?>

<ul>
	<?php foreach ($response->response->docs as $doc):
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl(BeerCrush::beer_id_to_brewery_id($doc->id)));
	?>
		<li><a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>"><?=$doc->name?></a> by <a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a></li>
	<?php endforeach;?>
</ul>

<?php

// Get a list of newest beers in this beer's (first) style
$url=$solr_url.'fl=id,name,ctime&start=0&rows=20&wt=json&sort=ctime+desc&q=style:'.$style->id;
// print $url;exit;
$response=json_decode(@file_get_contents($url));
// print_r($response);exit;

?>

<h2>Newest Beers</h2>
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

