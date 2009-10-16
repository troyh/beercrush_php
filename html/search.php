<?php

if (isset($_GET['q']))
{
	$results=file_get_contents('http://localhost:8080/solr/select/?wt=json&rows=20&qt=dismax&q='.urlencode($_GET['q']));
	$json=json_decode($results);
}

?>
<html>
<body>

<h1>Beer Crush</h1>

<form method="GET" action="search">
Search:
	<input type="text" size="60" name="q" value="<?php echo $json->responseHeader->params->q ?>"/>
	<input type="submit" name="Search" />
</form>
<?php
if (!empty($json))
{
?>
<h2>Results for "<?php echo $json->responseHeader->params->q ?>"</h2>
<ol>
<?php
	foreach ($json->response->docs as $doc)
	{
		print '<li>'.$doc->name."</li>";
	}
?>
</ol>

<?php echo $json->responseHeader->QTime/1000 ?> seconds
<?php
}
?>
<h2>Raw response</h2>
<pre>
<?php echo $results ?>
</pre>
</body>
</html>