<?php
header('Content-Type: text/html; charset=utf-8'); 

if (!isset($header))
	$header=array();
	
// Add $header types, if they aren't already set
foreach (array('css','js') as $t) {
	if (!isset($header[$t]))
		$header[$t]=array();
}

?>
<html>
<head>
	<title><?=isset($header['title'])?$header['title']:'Beer Crush'?></title>
	<link href="/css/BeerCrush.css" rel="stylesheet" type="text/css" />
	<link href="/css/jquery.autocomplete.css" rel="stylesheet" type="text/css" />
	<?=join("\n",$header['css'])?>
	
	<script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAtBVHEgzTr_SrDgMUCmnRJRQfXbV2W6YcYPLUqvTgqWubOD1G5hSaFaNTdVgdeM66iYgNhcbzSAGHNg"></script>
	<script type="text/javascript">
	google.load("jquery","1.4.1");
	</script>
	<script type="text/javascript" src="/js/jquery.cookie.js"></script>
	<script type="text/javascript" src="/js/json2.js"></script>
	<script type="text/javascript" src="/js/beercrush.js"></script>
	<script type="text/javascript" src="/js/jquery-autocomplete/jquery.autocomplete.js"></script>
	<?=join("\n",$header['js'])?>
</head>
<body>
	<div id="login"></div>
	<div id="logo"><a href="/"><img src="/img/Logosmall.jpg"></a></div>
	<ol id="navmenu">
		<li><a href="/beers/">Beers</a></li>
		<li><a href="/breweries/">Breweries</a></li>
		<li><a href="/places/">Places</a></li>
		<li><a href="/users/">People</a></li>
	</ol>
	<div id="findnearbylink"><a href="/nearby">Find Places Nearby</a></div>
	<div id="searchform">
		<form method="GET" action="/search">
			Search:
			<input type="text" id="searchbox" name="q" size="40" value="">
			<input type="submit" value="Go">
			<div>
				<input type="radio" name="dt" value="" checked="checked">All
				<input type="radio" name="dt" value="beersandbreweries">Beers/Breweries
				<input type="radio" name="dt" value="places">Places
				<input type="radio" name="dt" value="people">People
			</div>
		</form>
	</div>
	<div id="page_content">
	