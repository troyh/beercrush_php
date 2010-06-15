<?php
require_once('beercrush/beercrush.php');

define(SEARCH_RESULTS_PER_PAGE,20);

function pagenav($start,$perpage,$total) {
	$numpages=(int)($total/$perpage)+($total%$perpage?1:0);
	$curr_pg=(int)($start/$perpage)+1;

	$params=array();
	foreach ($_GET as $k=>$v) {
		switch ($k) {
			case "start":
				break;
			default:
				$params[]=$k.'='.$v;
				break;
		}
	}
	
	print "<div>";
	if ($curr_pg > 1)
		print '<a href="?'.((($curr_pg-2)*$perpage)?'start='.(($curr_pg-2)*$perpage).'&':'').join('&',$params).'">&lt;</a> ';
	for ($pg=1;$pg <= $numpages;++$pg) {
		if ($pg==$curr_pg)
			print $pg.' ';
		else
			print '<a href="?'.(($pg*$perpage-$perpage)?'start='.($pg*$perpage-$perpage).'&':'').join('&',$params).'">'.$pg.'</a> ';
	}
	if ($curr_pg<$numpages)
		print '<a href="?start='.($curr_pg*$perpage).'&'.join('&',$params).'">&gt;</a> ';
	print "</div>";
}

function print_top_nav($perpage,$total) {
	if (empty($_GET['start']) || $_GET['start']==0) {
		// Nothing
	}
	else {
		pagenav($_GET['start'],$perpage,$total);
	}
}

function print_bottom_nav($dt,$total,$perpage) {
	if ($total > $perpage) :
		if (empty($_GET['start']) || $_GET['start']==0) :
?>
			<a href="?dt=<?=$dt?>&start=<?=$perpage?>&q=<?=$_GET['q']?>">More...</a>
<?php
		else :
			pagenav($_GET['start'],$perpage,$total);
		endif;
	endif;
}

$styles_lookup=BeerCrush::api_doc($BC->oak,'/style/flatlist');

include("header.php");
?>

<?php
///////////////////////////////////////////
// Beers
///////////////////////////////////////////
if (empty($_GET['dt']) || $_GET['dt']=='beers') :
	$url='search?q='.urlencode($_GET['q']).'&doctype=beer&start='.(empty($_GET['start'])?0:$_GET['start']);
	$results=BeerCrush::api_doc($BC->oak,$url);
?>
<h2>Beers</h2>
<div>
<h3><?=$results->response->numFound?> Results</h3>
<?php
	print_top_nav(SEARCH_RESULTS_PER_PAGE,$results->response->numFound);
	foreach ($results->response->docs as $doc) : 
		$beer=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($beer->brewery_id));
?>
	<div>
		<?php if ($beer->photos->total):?><img src="<?=$beer->photos->thumbnail?>" /><?php endif;?>
		<a href="/<?=BeerCrush::docid_to_docurl($beer->id)?>"><?=$beer->name?></a> by <a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a>
		(<?=$beer->review_summary->avg?>)
		<?php if (!empty($beer->styles[0])) :?>
			(<a href="/style/<?=$styles_lookup->{$beer->styles[0]}->id?>"><?=$styles_lookup->{$beer->styles[0]}->name?></a>)
		<?php endif; ?>
		
	</div>
<?php 
	endforeach;
	print_bottom_nav('beers',$results->response->numFound,SEARCH_RESULTS_PER_PAGE);
?>
</div>
<?php
endif;
?>

<?php
///////////////////////////////////////////
// Breweries
///////////////////////////////////////////
if (empty($_GET['dt']) || ($_GET['dt']=='beers' && empty($_GET['start'])) || $_GET['dt']=='breweries') :
	$url='search?q='.urlencode($_GET['q']).'&doctype=brewery&start='.(empty($_GET['start'])?0:$_GET['start']);
	$results=BeerCrush::api_doc($BC->oak,$url);
?>
<h2>Breweries</h2>
<div>
<h3><?=$results->response->numFound?> Results</h3>
<?php
	print_top_nav(SEARCH_RESULTS_PER_PAGE,$results->response->numFound);
	foreach ($results->response->docs as $doc):
		$brewery=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id)); ?>
	<div>
		<?php if ($brewery->photos->total):?><img src="<?=$brewery->photos->thumbnail?>" /><?php endif;?>
		<a href="/<?=BeerCrush::docid_to_docurl($brewery->id)?>"><?=$brewery->name?></a>
		<?=$brewery->address->city?>, <?=$brewery->address->state?> <?=$brewery->address->country?>
	</div>
<?php
	endforeach; 
	print_bottom_nav('breweries',$results->response->numFound,SEARCH_RESULTS_PER_PAGE); ?>
</div>
<?php
endif;
?>

<?php
///////////////////////////////////////////
// Places
///////////////////////////////////////////
if (empty($_GET['dt']) || $_GET['dt']=='places') :
	$url='search?q='.urlencode($_GET['q']).'&doctype=place&start='.(empty($_GET['start'])?0:$_GET['start']);
	$results=BeerCrush::api_doc($BC->oak,$url);
?>
<h2>Places</h2>
<div>
<h3><?=$results->response->numFound?> Results</h3>
<?php
	print_top_nav(SEARCH_RESULTS_PER_PAGE,$results->response->numFound);
	foreach ($results->response->docs as $doc) :
		$place=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
?>
	<div>
		<?php if ($place->photos->total):?><img src="<?=$place->photos->thumbnail?>" /><?php endif;?>
		<a href="/<?=BeerCrush::docid_to_docurl($place->id)?>"><?=$place->name?></a>
		(<?=$place->placetype?>)
		(<?=$place->review_summary->avg?>)
		<?=$place->address->city?>, <?=$place->address->state?> <?=$place->address->country?>
	</div>
<?php
	endforeach; 
	print_bottom_nav('places',$results->response->numFound,SEARCH_RESULTS_PER_PAGE);
?>
</div>
<?php
endif;
?>

<?php
///////////////////////////////////////////
// Members
///////////////////////////////////////////
if (empty($_GET['dt']) || $_GET['dt']=='people') :
	$url='search?q='.urlencode($_GET['q']).'&doctype=user&start='.(empty($_GET['start'])?0:$_GET['start']);
	$results=BeerCrush::api_doc($BC->oak,$url);
?>
<h2>Members</h2>
<div>
<h3><?=$results->response->numFound?> Results</h3>
<?php
	print_top_nav(SEARCH_RESULTS_PER_PAGE,$results->response->numFound);
	foreach ($results->response->docs as $doc) : 
		$user=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
?>
	<div>
		<?php if (!empty($user->avatar)):?><img src="<?=$user->avatar?>" /><?php endif;?>
		<a href="/<?=BeerCrush::docid_to_docurl($user->id)?>"><?=$user->name?></a>
		<?php if (!empty($user->address)): ?><?=$user->address->city?>, <?=$user->address->state?> <?=$user->address->country?><?php endif;?>
	</div>
<?php
	endforeach; 
	print_bottom_nav('people',$results->response->numFound,SEARCH_RESULTS_PER_PAGE);
?>
</div>
<?php
endif;
?>

<?php
///////////////////////////////////////////
// Styles
///////////////////////////////////////////
if (empty($_GET['dt']) || ($_GET['dt']=='beers' && empty($_GET['start'])) || $_GET['dt']=='styles') :
	$url='search?q='.urlencode($_GET['q']).'&doctype=style&start='.(empty($_GET['start'])?0:$_GET['start']);
	$results=BeerCrush::api_doc($BC->oak,$url);
?>
<h2>Styles</h2>
<div>
<h3><?=$results->response->numFound?> Results</h3>
<?php
	print_top_nav(SEARCH_RESULTS_PER_PAGE,$results->response->numFound);
	foreach ($results->response->docs as $doc) : 
		$styledoc=BeerCrush::api_doc($BC->oak,BeerCrush::docid_to_docurl($doc->id));
?>
		<div>
<?php 
if (!empty($styledoc->hierarchy)) :
	foreach ($styledoc->hierarchy as $style):
?>
		<a href="/<?=BeerCrush::docid_to_docurl($style->id)?>"><?=$style->name?></a> &gt;
<?php
	endforeach;
endif;
?>
		<a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>"><?=$doc->name?></a>
	</div>
<?php
	endforeach; 
	print_bottom_nav('styles',$results->response->numFound,SEARCH_RESULTS_PER_PAGE);
?>
</div>
<?php
endif;
?>

<?php
if (empty($_GET['dt']) || $_GET['dt']=='places' || $_GET['dt']=='locations') :
	$url='search?q='.urlencode($_GET['q']).'&doctype=location&start='.(empty($_GET['start'])?0:$_GET['start']);
	$results=BeerCrush::api_doc($BC->oak,$url);
?>
<h2>Locations</h2>
<div>
<h3><?=$results->response->numFound?> Results</h3>
<?php
	print_top_nav(SEARCH_RESULTS_PER_PAGE,$results->response->numFound);
	foreach ($results->response->docs as $doc) :
?>
	<div>
		<a href="/<?=BeerCrush::docid_to_docurl($doc->id)?>/"><?=$doc->name?></a>
	</div>
<?php
	endforeach;
	print_bottom_nav('locations',$results->response->numFound,SEARCH_RESULTS_PER_PAGE);
?>
</div>
<?php
endif;
?>

<?php
include("footer.php");
?>