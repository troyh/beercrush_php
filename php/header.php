<?php
header('Content-Type: text/html; charset=utf-8'); 
?>
<html>
<head>
	<title>Beer Crush</title>
	<link href="/css/BeerCrush.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAtBVHEgzTr_SrDgMUCmnRJRQfXbV2W6YcYPLUqvTgqWubOD1G5hSaFaNTdVgdeM66iYgNhcbzSAGHNg"></script>
	<script type="text/javascript">
	google.load("jquery","1.4.1");
	</script>
	<script type="text/javascript" src="/js/jquery.cookie.js"></script>
	<script type="text/javascript" src="/js/json2.js"></script>
	<script type="text/javascript" src="/js/beercrush.js"></script>
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
	<div id="searchform">
		<form method="GET" action="/php/search">
			Search:
			<input type="text" name="q" size="40" value="">
			<input type="submit" value="Go">
			<div>
				<input type="radio" name="dt" value="" checked="checked">All
				<input type="radio" name="dt" value="beers">Beers/Breweries
				<input type="radio" name="dt" value="place">Places
				<input type="radio" name="dt" value="place">People
			</div>
		</form>
	</div>
	<div id="page_content">
	