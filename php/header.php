<?php
header('Content-Type: text/html; charset=utf-8'); 
?>
<html>
<head>
	<title>Beer Crush</title>
	<link href="/css/BeerCrush.css" rel="stylesheet" type="text/css" />
	<script src="http://www.google.com/jsapi"></script>
	<script type="text/javascript">
	function getUrlVars()
	{
	    var vars = [], hash;
	    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	    for(var i = 0; i < hashes.length; i++)
	    {
	        hash = hashes[i].split('=');
	        vars.push(hash[0]);
	        vars[hash[0]] = hash[1];
	    }
	    return vars;
	}
	</script>
</head>
<body>
	<a href="/"><img src="/img/Logosmall.jpg"></a>
	<ol id="navmenu">
		<li><a href="/beers/">Beers</a></li>
		<li><a href="/brewery/">Breweries</a></li>
		<li><a href="/place/">Places</a></li>
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
			</div>
		</form>
	</div>
	<div id="page_content">
	