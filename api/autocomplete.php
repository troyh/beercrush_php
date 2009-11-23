<?php
define('DATAFILENAME',"/var/local/BeerCrush/meta/autocomplete_names.tsv");

define('UNKNOWN',0);
define('BEER',1);
define('BREWERY',2);
define('PLACE',4);
define('STYLE',128);

$beer_styles=array(
	"Amber Hybrid Beer",
	"American Ale",
	"American Amber Ale",
	"American Barleywine",
	"American Brown Ale",
	"American IPA",
	"American Pale Ale",
	"American Stout",
	"American Wheat or Rye Beer",
	"Baltic Porter",
	"Belgian Blond Ale",
	"Belgian Dark Strong Ale",
	"Belgian Dubbel",
	"Belgian Golden Strong Ale",
	"Belgian Pale Ale",
	"Belgian Specialty Ale",
	"Belgian Strong Ale",
	"Belgian Tripel",
	"Belgian and French Ale",
	"Berliner Weisse",
	"Bière de Garde",
	"Blonde Ale",
	"Bock",
	"Bohemian Pilsener",
	"Brown Porter",
	"California Common Beer",
	"Christmas/Winter Specialty Spiced Beer",
	"Classic American Pilsner",
	"Classic Rauchbier",
	"Cream Ale",
	"Dark American Lager",
	"Dark Lager",
	"Doppelbock",
	"Dortmunder Export",
	"Dry Stout",
	"Dunkelweizen",
	"Düsseldorf Altbier",
	"Eisbock",
	"English Barleywine",
	"English Brown Ale",
	"English IPA",
	"English Pale Ale",
	"European Amber Lager",
	"Extra Special/Strong Bitter (English Pale Ale)",
	"FRUIT BEER",
	"Flanders Brown Ale/Oud Bruin",
	"Flanders Red Ale",
	"Foreign Extra Stout",
	"Fruit Beer",
	"Fruit Lambic",
	"German Pilsner (Pils)",
	"German Wheat and Rye Beer",
	"Gueuze",
	"Imperial IPA",
	"India Pale Ale(IPA)",
	"Irish Red Ale",
	"Kölsch",
	"Light Hybrid Beer",
	"Light Lager",
	"Lite American Lager",
	"Maibock/Helles Bock",
	"Mild",
	"Munich Dunkel",
	"Munich Helles",
	"Northern English Brown Ale",
	"Northern German Altbier",
	"Oatmeal Stout",
	"Oktoberfest/Märzen",
	"Old Ale",
	"Other Smoked Beer",
	"Pilsner",
	"Porter",
	"Premium American Lager",
	"Robust Porter",
	"Roggenbier (German Rye Beer)",
	"Russian Imperial Stout",
	"Saison",
	"Schwarzbier (Black Beer)",
	"Scottish Export 80/-",
	"Scottish Heavy 70/-",
	"Scottish Light 60/-",
	"Scottish and Irish Ale",
	"Smoke-Flavored/Wood-Aged Beer",
	"Sour Ale",
	"Southern English Brown",
	"Special/Best/Premium Bitter",
	"Specialty Beer",
	"Specialty Beer",
	"Spice, Herb, or Vegetable Beer",
	"Spice/Herb/Vegetable Beer",
	"Standard American Lager",
	"Standard/Ordinary Bitter",
	"Stout",
	"Straight (Unblended) Lambic",
	"Strong Ale",
	"Strong Scotch Ale",
	"Sweet Stout",
	"Traditional Bock",
	"Vienna Lager",
	"Weizen/Weissbier",
	"Weizenbock",
	"Witbier",
	"Wood-Aged Beer",
);

function loadDataFile($fname, &$names, &$types)
{
	$fp=fopen($fname,'r');
	if ($fp)
	{
		while (!feof($fp))
		{
			$line=fgets($fp);
			if (!feof($fp))
			{
				// list($txt,$id)=preg_split('/\t/',$line);
				$names[]=trim($line);
				// if (!strncasecmp($id,'beer:',5))
				// 	$types[]=BEER;
				// else if (!strncasecmp($id,'brewery:',8))
				// 	$types[]=BREWERY;
				// else if (!strncasecmp($id,'place:',6))
				// 	$types[]=PLACE;
			}
		}
		
		fclose($fp);
	}
}

function autocomplete($query,$list,$searchable_types,$requested_types)
{
	if (count($list)==0)
		return;
		
	// Binary-search list
	$hi=count($list);
	$lo=$hi>0?1:$hi;
	// lo and hi are 1-based so that we can decrement lo to zero without wrapping around
	while ($lo<=$hi)
	{
		$mid=($hi+$lo)/2;
		// Remember, mid is 1-based, so use mid-1 to reference array items
		$cmp=strncasecmp($query,$list[$mid-1],strlen($query));
		if ($cmp<0)
		{
			$hi=$mid-1;
		}
		else if ($cmp>0)
		{
			$lo=$mid+1;
		}
		else
		{
			// Match, go backwards until we find the first one that doesn't match
			do
			{
				--$mid;
			}
			while ($mid && strncasecmp($query,$list[$mid-1],strlen($query)) == 0);
			
			// mid is now before the 1st that matches, so spit out the names until it no longer matches
			do
			{
				++$mid;
				if (strncasecmp($query,$list[$mid-1],strlen($query))==0)
				{
					print $list[$mid-1]."\n";
					// See if it's the correct type we want
					// if (($requested_types==0) || ($searchable_types[$mid-1] & $requested_types))
					// {
					// 	print $list[$mid-1]."\t\n";
					// }
				}
				else
					break;
			}
			while ($mid<count($list));
			break;
		}
	}
}

// Searchable data structures
$shared_data=array();

// Create the shared mem, if it's not yet there
// $shm_key=ftok(__FILE__,'t');
// $shm=@shmop_open($shm_key,'a',0644,0);
$shared_data=apc_fetch('autocomplete_data',$in_cache);
if ($in_cache===FALSE)
{
	// shmop_delete($shm);
	// print "Loading from file\n";
	$searchable_names=array();
	$searchable_types=array();
	loadDataFile(DATAFILENAME,&$searchable_names,&$searchable_types);

	$shared_data=array(
		'names' => $searchable_names,
		'types' => $searchable_types,
	);
	
	apc_store('autocomplete_data',$shared_data);
	
	// // $sd=serialize($shared_data);
	// $sd=json_encode($shared_data);
	// $test=json_decode($sd);
	// if ($test==NULL)
	// 	print "json_decode() failed\n";
	// 
	// $shm=shmop_open($shm_key,'c',0644,strlen($sd)); // +256 just to give it some extra room
	// if ($shm===FALSE)
	// {
	// }
	// else
	// {
	// 	shmop_write($shm,$sd,0);
	// 	shmop_close($shm);
	// 	print "Re-saved data, size=".strlen($sd)."\n";
	// 	var_dump($test);
	// }
}
else
{
	// print "Grabbing from APC\n";
	// print "Grabbing from shmem\n";
	// print "Size:".shmop_size($shm)."\n";
	// $shared_data=unserialize(shmop_read($shm,0,shmop_size($shm)));
	// $sd=shmop_read($shm,0,shmop_size($shm));
	// $shared_data=json_decode($sd);
	
	// var_dump($shared_data);
	// shmop_close($shm);
}

header("Content-Type: text/plain");
// var_dump($shared_data);exit;
$dataset_mask=UNKNOWN;
if (!strlen($_GET['dataset']))
	$dataset_mask=BEER|BREWERY|PLACE;
else if (!strcasecmp($_GET['dataset'],"beersandbreweries"))
	$dataset_mask=BEER|BREWERY;
else if (!strcasecmp($_GET['dataset'],"beers"))
	$dataset_mask=BEER;
else if (!strcasecmp($_GET['dataset'],"places"))
	$dataset_mask=PLACE;
else if (!strcasecmp($_GET['dataset'],"bjcp_style"))
	$dataset_mask=STYLE;
else
	$dataset_mask=BEER|BREWERY|PLACE;

if ($dataset_mask==STYLE)
{
	// autocomplete($_GET['q'],$beer_styles,0);
}
else
{
	autocomplete($_GET['q'],$shared_data['names'],$shared_data['types'],$dataset_mask);
}

?>
