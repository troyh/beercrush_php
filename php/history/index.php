<?php
require_once('beercrush/beercrush.php');
$oak=new OAK(BeerCrush::CONF_FILE);

$params=array();
if (!empty($_GET['before']))
	$params[]='before='.$_GET['before'];
if (!empty($_GET['after']))
	$params[]='after='.$_GET['after'];
if (!empty($_GET['pg']))
	$params[]='pg='.$_GET['pg'];
	
$url='history/?'.implode('&',$params);
$doc=BeerCrush::api_doc($oak,$url);
include('../header.php');

// Navigation
$most_recent=strtotime($doc->changes[0]->date);
$least_recent=strtotime($doc->changes[count($doc->changes)-1]->date);
print "Older than <a href=\"./?before=".date('Y/m/d H:i:s',$least_recent)."\">".date('Y/m/d H:i:s',$least_recent)."</a>";
print "Newer than <a href=\"./?after=".date('Y/m/d H:i:s',$most_recent)."\">".date('Y/m/d H:i:s',$most_recent)."</a>";

foreach ($doc->changes as $change) {
	print "<div>";
	// print "<div>".$change->commit."</div>";
	print "<div>".$change->date."</div>";

	$changed_doc=BeerCrush::api_doc($oak,BeerCrush::docid_to_docurl($change->docid));
	
	print "<div><a href=\"/".BeerCrush::docid_to_docurl($change->docid)."\">".$changed_doc->name."</a></div>";
	// print_r($change->change);
	
	// Show changes
	if (!is_object($change->change->old) || !is_object($change->change->new))
		continue;
		
	$all_keys=array_keys(get_object_vars($change->change->old));
	$all_keys=array_unique(array_merge($all_keys,array_keys(get_object_vars($change->change->new))));
	
	// print_r($all_keys);
	foreach ($all_keys as $k) {
		if (isset($change->change->old->$k) && isset($change->change->new->$k)) { // It's a change
			if ($change->change->old->$k === $change->change->new->$k) {
				print "<div>No change to $k</div>";
			}
			else {
				print "<div>Changed $k from".$change->change->old->$k.' to '.$change->change->new->$k."</div>";
			}
		}
		else if (isset($change->change->old->$k)) { // It's been removed
			print "<div>Removed $k=".$change->change->old->$k."</div>";
		}
		else if (isset($change->change->new->$k)) { // It's been added
			print "<div>Added $k=".$change->change->new->$k."</div>";
		}
	}
	
	print "</div>";
}

include('../footer.php');

?>