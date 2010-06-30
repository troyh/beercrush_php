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
	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/ui-lightness/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<?=join("\n",$header['css'])?>
	
	<script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAtBVHEgzTr_SrDgMUCmnRJRQfXbV2W6YcYPLUqvTgqWubOD1G5hSaFaNTdVgdeM66iYgNhcbzSAGHNg"></script>
	<script type="text/javascript">
	google.load("jquery","1.4.2");
	google.load("jqueryui", "1.8.2");	
	</script>
	<script type="text/javascript" src="/js/jquery.cookie.js"></script>
	<script type="text/javascript" src="/js/json2.js"></script>
	<script type="text/javascript" src="/js/beercrush.js"></script>
	<script type="text/javascript" src="/js/jquery-autocomplete/jquery.autocomplete.js"></script>
	<?=join("\n",$header['js'])?>
</head>
<body>
<div id="page_wrap">
	<div id="header" class="clearfix">
		<div id="logo"><a href="/home.php"><img src="/img/Logosmall.jpg"></a></div>
		<div id="header_main">
			<div id="tabs_wrap">
				<ol id="navmenu" class="module">
					<li class="selected"><a href="/beers/">Beers</a></li>
					<li><a href="/breweries/">Breweries</a></li>
					<li><a href="/places/">Places</a></li>
					<li id="findnearbylink"><a href="/nearby">Nearby</a></li>
				</ol>
				<!--GAY REMOVE SUBMENU
				<ol id="submenu" class="module">
					<li><a href="">some text here maybe</a></li>
				</ol>
				-->
			</div>
			<div id="searchform">
				<form method="GET" action="/search">
					Search:
					<input type="text" id="searchbox" name="q" size="40" value="<?=empty($_GET['q'])?"":trim($_GET['q']);?>">
					<input type="submit" value="Go">
					<div>
						<input type="radio" name="dt" value=""       <?=empty($_GET['dt'])   ?"checked=\"checked\"":""?>>All
						<input type="radio" name="dt" value="beers"  <?=$_GET['dt']=='beers' ?"checked=\"checked\"":""?>>Beers/Breweries
						<input type="radio" name="dt" value="places" <?=$_GET['dt']=='places'?"checked=\"checked\"":""?>>Places
						<input type="radio" name="dt" value="people" <?=$_GET['dt']=='people'?"checked=\"checked\"":""?>>People
					</div>
				</form>
			</div>
			<div id="login" class="white"></div>
		</div>
	</div>
	<div id="page_content" class="clearfix">
	