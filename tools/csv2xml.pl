#!/usr/bin/perl -w

use utf8;
use HTML::Entities qw(encode_entities_numeric);

# Fields in CSV file:
# -------------------
# 0 Brewer
# 1 size
# 2 beer name
# 3 style
# 4 description from brewer
# 5 ABV
# 6 OG
# 7 FG
# 8 IBU
# 9 availability
# 10 calories
# 11 calories/serving size
# 12 ingredients
# 13 grains
# 14 hops
# 15 yeast
# 16 other ingredients
# 17 distributor
# 18 item
# 19 upc
# 20 reg price
# 21 post off
# 22 net case price
# 23 unit price
# 24 deposit
# 25 suggested +20%
# 26 suggested +25%
# 27 suggested +30%
# 28 post off unit

my %bjcp_styles=(
# Taken from:
# xmlstarlet sel -t -m "/styleguide/class[@type='beer']/category|/styleguide/class[@type='beer']/category/subcategory" -v @id -o "&#09;" -v name -n styleguide2008.xml | sort -n
#
"1A"	=> "Lite American Lager",
"1B"	=> "Standard American Lager",
"1C"	=> "Premium American Lager",
"1D"	=> "Munich Helles",
"1E"	=> "Dortmunder Export",
"1"		=> "Light Lager",
"2A"	=> "German Pilsner (Pils)",
"2B"	=> "Bohemian Pilsener",
"2C"	=> "Classic American Pilsner",
"2"		=> "Pilsner",
"3A"	=> "Vienna Lager",
"3B"	=> "Oktoberfest/Märzen",
"3"		=> "European Amber Lager",
"4A"	=> "Dark American Lager",
"4B"	=> "Munich Dunkel",
"4C"	=> "Schwarzbier (Black Beer)",
"4"		=> "Dark Lager",
"5A"	=> "Maibock/Helles Bock",
"5" 	=> "Bock",
"5B"	=> "Traditional Bock",
"5C"	=> "Doppelbock",
"5D"	=> "Eisbock",
"6A"	=> "Cream Ale",
"6B"	=> "Blonde Ale",
"6C"	=> "Kölsch",
"6D"	=> "American Wheat or Rye Beer",
"6"		=> "Light Hybrid Beer",
"7"		=> "Amber Hybrid Beer",
"7A"	=> "Northern German Altbier",
"7B"	=> "California Common Beer",
"7C"	=> "Düsseldorf Altbier",
"8A"	=> "Standard/Ordinary Bitter",
"8B"	=> "Special/Best/Premium Bitter",
"8C"	=> "Extra Special/Strong Bitter (English Pale Ale)",
"8"		=> "English Pale Ale",
"9A"	=> "Scottish Light 60/-",
"9B"	=> "Scottish Heavy 70/-",
"9C"	=> "Scottish Export 80/-",
"9D"	=> "Irish Red Ale",
"9E"	=> "Strong Scotch Ale",
"9"		=> "Scottish and Irish Ale",
"10A"	=> "American Pale Ale",
"10"	=> "American Ale",
"10B"	=> "American Amber Ale",
"10C"	=> "American Brown Ale",
"11A"	=> "Mild",
"11B"	=> "Southern English Brown",
"11C"	=> "Northern English Brown Ale",
"11"	=> "English Brown Ale",
"12A"	=> "Brown Porter",
"12B"	=> "Robust Porter",
"12C"	=> "Baltic Porter",
"12"	=> "Porter",
"13A"	=> "Dry Stout",
"13B"	=> "Sweet Stout",
"13C"	=> "Oatmeal Stout",
"13D"	=> "Foreign Extra Stout",
"13E"	=> "American Stout",
"13F"	=> "Russian Imperial Stout",
"13"	=> "Stout",
"14A"	=> "English IPA",
"14B"	=> "American IPA",
"14C"	=> "Imperial IPA",
"14"	=> "India Pale Ale(IPA)",
"15A"	=> "Weizen/Weissbier",
"15B"	=> "Dunkelweizen",
"15C"	=> "Weizenbock",
"15D"	=> "Roggenbier (German Rye Beer)",
"15"	=> "German Wheat and Rye Beer",
"16A"	=> "Witbier",
"16B"	=> "Belgian Pale Ale",
"16"	=> "Belgian and French Ale",
"16C"	=> "Saison",
"16D"	=> "Bière de Garde",
"16E"	=> "Belgian Specialty Ale",
"17A"	=> "Berliner Weisse",
"17B"	=> "Flanders Red Ale",
"17C"	=> "Flanders Brown Ale/Oud Bruin",
"17D"	=> "Straight (Unblended) Lambic",
"17E"	=> "Gueuze",
"17F"	=> "Fruit Lambic",
"17"	=> "Sour Ale",
"18A"	=> "Belgian Blond Ale",
"18B"	=> "Belgian Dubbel",
"18"	=> "Belgian Strong Ale",
"18C"	=> "Belgian Tripel",
"18D"	=> "Belgian Golden Strong Ale",
"18E"	=> "Belgian Dark Strong Ale",
"19A"	=> "Old Ale",
"19B"	=> "English Barleywine",
"19C"	=> "American Barleywine",
"19"	=> "Strong Ale",
"20A"	=> "FRUIT BEER",
"20"	=> "Fruit Beer",
"21A"	=> "Spice, Herb, or Vegetable Beer",
"21B"	=> "Christmas/Winter Specialty Spiced Beer",
"21"	=> "Spice/Herb/Vegetable Beer",
"22A"	=> "Classic Rauchbier",
"22B"	=> "Other Smoked Beer",
"22C"	=> "Wood-Aged Beer",
"22"	=> "Smoke-Flavored/Wood-Aged Beer",
"23A"	=> "Specialty Beer",
"23"	=> "Specialty Beer",
);

# Styles
my %styles_map=
(
"AMERICAN BROWN" 		=> "10C",
"AMERICAN CREAM ALE" 	=> "6A",
"AMERICAN IPA"			=> "14B",
"AMERICAN PALE ALE"		=> "10A",
"AMERICAN WHEAT" 		=> "6D",
"BARLEYWINE" 			=> "19C",
"BELGIAN ALE"			=> "16",
"BELGIAN AMBER ALE"		=> "16B",
"BELGIAN STRONG ALE" 	=> "18",
"BELGIAN WIT" 			=> "16A",
"BLONDE ALE"			=> "6B",
"BROWN ALE"				=> "10C",
"DOPPLEBOCK" 			=> "5C",
"ESB"					=> "8C",
"ENGLISH PORTER" 		=> "12",
"GERMAN SPELT ALE"		=> "15",
"HEFEWEIZEN" 			=> "6D",
"IPA"					=> "14",
"IMPERIAL IPA" 			=> "14C",
"IMPERIAL STOUT"		=> "13F",
"LAGER" 				=> "1C",
"OUD BRUIN"				=> "17C",
"PALE ALE"				=> "10A",
"PORTER"				=> "12",
"SCOTCH ALE" 			=> "9E",
"STOUT"					=> "13",
"WITBIER"				=> "16A",
"CREAM STOUT" 			=> "6A",
);

# Breweries
my %brewery_map=
(
"33 EXPORT" 						=> "Dominion-Breweries",
"ABITA" 							=> "Abita-Brewing-Co-LLC",
"ACME BREWING",						=> "North-Coast-Brewing-Co",
"AKTIEN" 							=> "Aktien-Brauerei-Kaufbeuren-AG",
"ALASKAN" 							=> "Alaskan-Brewing-Co",
"ALLAGASH BREWING CO.",				=> "Allagash-Brewing-Co-Inc",
"ALLAGASH" 							=> "Allagash-Brewing-Co-Inc",
"ALLGAUER" 							=> "Allgäuer-Brauhaus-AG",
"ALPINE BREWING",					=> "Alpine-Beer-Co",
"ALTENMUNSTER" 						=> "Privatbrauerei-Franz-Joseph-Sailer",
"AMSTEL" 							=> "Heineken-USA",
"ANCHOR" 							=> "Anchor-Brewing-Co",
"ANDERSON VALLEY BREWING",			=> "Anderson-Valley-Brewing-Co",
"ASAHI" 							=> "Asahi-Breweries-Ltd",
"AVERY BREWING",					=> "Avery-Brewing-Company",
"AVERY" 							=> "Avery-Brewing-Company",
"AYINGER" 							=> "Ayinger-Brewery",
"BARBAR" 							=> "Brasserie-Lefebvre",
"BARDS TALE" 						=> "Bards-Tale-Beer-Co-Llc",
"BARON" 							=> "Baron-Brewing",
"BAYERN" 							=> "Bayern-Brewing-Inc",
"BEAMISH" 							=> "Beamish-and-Crawford-Plc",
"BEAR REPUBLIC BREWING",			=> "Bear-Republic-Brewing-Co",
"BELGIUM" 							=> "Andelot-Brewery",
"BELHAVEN" 							=> "Belhaven-Brewery",
"BEND BREWING" 						=> "Bend-Brewing-Co",
"BIG SKY" 							=> "Big-Sky-Brewing-Co",
"BITBURGER" 						=> "Bitburger-Braugruppe-GmbH",
"BLACK SHEEP" 						=> "Black-Sheep-Brewery",
"BLANCHE" 							=> "Brooklyn-Brewery-Brooklyn",
"BLUE MOON" 						=> "Blue-Moon-Brewing-Co",
"BOHEMIA" 							=> "Bohemia-Beer",
"BOSTEELS" 							=> "Bosteels-Brewery",
"BOULDER" 							=> "Boulder-Beer-Company",
"BREWDOG" 							=> "BrewDog-Brewery",
"BRIDGEPORT" 						=> "Bridgeport-Brewing-Co",
"BRUGSE ZOT" 						=> "Brouwerij-Straffe-Hendrik",
"BUCKLER NA" 						=> "Heineken-USA",
"BUFFALO BILLS" 					=> "Buffalo-Bills-Brew-Pub-Hayward",
"BUTTE CREEK" 						=> "Butte-Creek-Brewing-Co",
"CAGUAMA" 							=> "Cerveceria-La-Constancia-SA",
"CALDERA" 							=> "Caldera-Brewing-Co",
"CAMO" 								=> "Camo-Brewing-Co",
"CARLSBERG" 						=> "Carlsberg-Breweries",
"CARTA BLANCA" 						=> "Cerveceria-Cuauhtemoc-Moctezuma ",
"CASCADE LAKES BREWING COMPANY"		=> "Cascade-Lakes-Brewing-Co",
"CHANG BEER" 						=> "IBHL-USA",
"CHIMAY" 							=> "Bieres-de-Chimay",
"CLIMAX NOEL" 						=> "Eel-River-Brewing-Co-Fortuna",
"CONEY ISLAND CRAFT LAGERS",		=> "Schmaltz-Brewing-Company",
"COOPERS" 							=> "Coopers-Brewery",
"CORONA LIGHT" 						=> "Grupo-Modelo",
"CORONA" 							=> "Grupo-Modelo",
"CUSQUENA" 							=> "Backus-and-Johnston",
"DE BOOMGAARD" 						=> "Leifmans-Brewery",
"DE GAYANT" 						=> "Les-Brasseurs-de-Gayant",
"DELIRIUM" 							=> "Huyghe-Brewery",
"DESCHUTES"							=> "Deschutes-Brewery-Inc",
"DIAMOND KNOT" 						=> "Diamond-Knot-Brewing-Company",
"DIXIE" 							=> "Dixie-Brewing-Company",
"DOGFISH HEAD BREWERY",				=> "Dogfish-Head-Craft-Brewery-Milton",
"DOGFISH HEAD" 						=> "Dogfish-Head-Craft-Brewery-Milton",
"DOS EQUIS" 						=> "Cervezas-Mexicanas",
"DU PONT" 							=> "La-Brasserie-Dupont",
"DUNDEE" 							=> "Dundee-Brewing-Company",
"EEL RIVER" 						=> "Eel-River-Brewing-Co-Fortuna",
"EGGENBERGER" 						=> "Eggenberger-Brewery",
"ELEPHANT" 							=> "Carlsberg-Breweries",
"ELK ROCK" 							=> "Pyramid-Breweries-Inc",
"ELYSIAN BREWING COMPANY",			=> "Elysian-Brewing-Company",
"ELYSIAN" 							=> "Elysian-Brewing-Company",
"ERDINGER" 							=> "Erdinger-Brewery",
"ESTRELLA DE GALICIA" 				=> "Estrella-de-Galicia",
"FIRESTATION 5" 					=> "Fire-Station-5-Brewing-Company",
"FISCHER" 							=> "Fischer-Brewery",
"FISH TALE" 						=> "Fish-Brewing-Co",
"FLYING DOG" 						=> "Flying-Dog-Brewery-Offices",
"FOSTERS" 							=> "Fosters-Brewery",
"FRANZISKANER" 						=> "Franziskaner-Brewery",
"FULL SAIL" 						=> "Full-Sail-Brewing-Company-Admin-Ofc",
"FULLERS" 							=> "Fullers-Brewery",
"GINGA KOGEN" 						=> "Ginga-Kogen-Brewery",
"GLACIER BREWHOUSE" 				=> "Glacier-Brew-House",
"GOUDEN CAROLUS" 					=> "Gouden-Carolus-Brewery",
"GRAND TETON" 						=> "Grand-Teton-Brewing-Co",
"GREAT DIVIDE",						=> "Great-Divide-Brewing-Co",
"GREEN FLASH" 						=> "Green-Flash-Brewing-Co",
"GREENE" 							=> "Greene-King-Morland-Brewery",
"GREENS" 							=> "Greens-Brewery",
"GROLSCH" 							=> "Grolsch-Brewery",
"GUINNESS" 							=> "Guinness-Brewery",
"GULDEN DRAAK" 						=> "Gulden-Draak-Brewery",
"HACKER PSCHORR" 					=> "Hacker-Pschorr-Brewery",
"HAIR OF DOG" 						=> "Hair-of-the-Dog-Brewing-Co",
"HAIR OF THE DOG",					=> "Hair-of-the-Dog-Brewing-Co",
"HALE'S ALES",						=> "Hales-Ales-Pub",
"HARP LAGER" 						=> "Harp-Brewery",
"HE'BREW",							=> "Schmaltz-Brewing-Company",
"HEINEKEN LIGHT" 					=> "Heineken-USA",
"HEINEKEN" 							=> "Heineken-USA",
"HENRY WEINHARDS" 					=> "Henry-Weinhards-Brewery",
"HI GRAVITY" 						=> "Hi-Gravity-Brewery",
"HINANO" 							=> "Hinano-Brewery",
"HOLLAND 1620" 						=> "Bavaria-Brouwerij",
"HOOD CANAL" 						=> "Hood-Canal-Brewery-Kingston",
"ICEHOUSE" 							=> "Miller-Brewing-Co",
"JOLLY PUMPKIN ARTISAN ALES",		=> "Jolly-Pumpkin-Artisan-Ales",
"KALIBER NA" 						=> "Guinness-Brewery",
"KASTEEL" 							=> "Brouwerij-Van-Honsebrouck",
"KEYSTONE ICE" 						=> "Molson-Coors-Brewing-Company",
"KEYSTONE LIGHT" 					=> "Molson-Coors-Brewing-Company",
"KEYSTONE" 							=> "Molson-Coors-Brewing-Company",
"KILLIAN" 							=> "Molson-Coors-Brewing-Company",
"KONIG" 							=> "König-Brauerei-GmbH",
"KONINGSHOEVEN" 					=> "Brouwerij-de-Koningshoeven",	
"KRONENBOURG" 						=> "Brasseries-Kronenbourg",
"KRUSOVICE" 						=> "Krušovice-Brewery",
"LAGUNITAS" 						=> "Lagunitas-Brewing-Co",
"LANG CREEK" 						=> "Lang-Creek-Brewery-Marion",
"LANG CREEK" 						=> "Lang-Creek-Brewery-Marion",
"LAUGHING BUDDHA BREWING CO.",		=> "Laughing-Buddha-Brewing",
"LAUGHING DOG" 						=> "Laughing-Dog-Brewing",
"LAZY BOY" 							=> "Lazy-Boy-Brewing-Co",
"LEAVENWORTH" 						=> "Leavenworth-Brewery",
"LEFT HAND" 						=> "Left-Hand-Brewing-Co",
"LEINENKUGEL" 						=> "Jacob-Leinenkugel-Brewing-Co",
"LINDEMANS" 						=> "Brouwerij-Lindemans",
"LITE" 								=> "Miller-Brewing-Co",
"LOST ABBEY",						=> "Port-Brewing-Company",
"LOST COAST" 						=> "Lost-Coast-Brewery-and-Cafe",
"MAC & JACK" 						=> "Mac-and-Jacks-Brewery",
"MACTARNAHANS" 						=> "MacTarnahans-Brewing-Co",
"MAD RIVER" 						=> "Mad-River-Brewing-Company-Tasting-Room",
"MAGNUM" 							=> "Miller-Brewing-Co",
"MALHEUR" 							=> "Brouwerij-De-Landtsheer-NV",
"MATEVEZA" 							=> "California-Organic-Brewery",
"MEANTIME" 							=> "Meantime-Brewing-Co",	
"MENDOCINO" 						=> "Mendocino-Brewing-Co",
"METOLIUS" 							=> "Bend-Brewing-Co",
"MIDNIGHT SUN" 						=> "Midnight-Sun-Brewing-Co",
"MILLER CHILL" 						=> "Miller-Brewing-Co",
"MISSISSIPPI MUD" 					=> "Mississippi-Brewing-Co",
"MODELO ESP" 						=> "Grupo-Modelo",
"MOLSON" 							=> "Molson-Coors-Brewing-Company",
"MOOSEHEAD" 						=> "Moosehead-Breweries-Ltd",
"MORETTI" 							=> "Birra-Moretti",
"MOYLANS" 							=> "Moylans-Brewery-and-Restaurant",
"MT HOOD" 							=> "Mt-Hood-Brewing-Co",
"MURPHY STOUT" 						=> "Murphy-Brewery",
"NEGRA MODELO" 						=> "Grupo-Modelo",
"NEW BELGIUM BREWING",				=> "New-Belgium-Brewing",
"NEW BELGIUM" 						=> "New-Belgium-Brewing",
"NEW CASTLE" 						=> "Newcastle-Breweries",
"NINKASI" 							=> "Ninkasi-Brewing-Co-Eugene",
"NORTH COAST BREWING",				=> "North-Coast-Brewing-Co",
"NORTHERN LIGHTS" 					=> "Northern-Lights-Brewing-Co-Spokane",
"O'HARA'S" 							=> "OHaras-Brew-Pub-and-Restaurant",
"O'HARAS" 							=> "OHaras-Brew-Pub-and-Restaurant",
"OLD MILWAUKEE" 					=> "Schlitz-Brewing-Co",
"OLD SPECKLED HEN" 					=> "Greene-King-Morland-Brewery",
"OLD STYLE" 						=> "G-Heileman-Brewing-Co",
"OLDE ENGLISH" 						=> "Miller-Brewing-Co",
"OLYMPIA" 							=> "Pabst-Brewing-Co",
"OMMEGANG",							=> "Brewery-Ommegang",
"ORVAL" 							=> "Brasserie-d-Orval",
"OSKAR BLUES" 						=> "Oskar-Blues-Grill-and-Brew-Lyons",
"OTTER CREEK" 						=> "Otter-Creek-Brewing-Inc-Middlebury",
"OTTER HEAD" 						=> "Otter-Brewery-Ltd",
"PABST LIGHT" 						=> "Pabst-Brewing-Co",
"PABST" 							=> "Pabst-Brewing-Co",
"PACIFICO" 							=> "Grupo-Modelo",
"PALMA" 							=> "Servejaria-Sul-Brasileira",
"PAULANER" 							=> "Paulaner-Braeuerei-GmbH",
"PETER HOLLAND" 					=> "Oranjeboom-Brewery",
"PETES WICKED" 						=> "Petes-Brewing-Co-Inc",
"PIKE BREWING",						=> "Pike-Brewing-Co",
"PIKE" 								=> "Pike-Brewing-Co",
"PILSNER URQUELL" 					=> "SABMiller",
"PINKUS" 							=> "Pinkus-Muller-Brewery",
"PIRAAT TRIPLE" 					=> "Brouwerij-Van-Steenberge",
"POPERINGS " 						=> "Brewery-Van-Eecke",
"PORT BREWING",						=> "Port-Brewing-Company",
"PYRAMID" 							=> "Pyramid-Breweries-Inc",
"RADEBERGER" 						=> "Radeberger-Brewery",
"RED STRIPE" 						=> "Desnoes-and-Geddes",
"ROCHEFORT TRAPPIST" 				=> "Brasserie-de-Rochefort",
"ROGUE" 							=> "Rogue-Brewery",
"ROOTS" 							=> "Roots-Organic-Brewing-Co",
"RUDDLES COUNTY ALE" 				=> "Ruddles-Brewery",
"RUSSIAN RIVER BREWING",			=> "Russian-River-Brewing-Co",
"RUSSIAN RIVER" 					=> "Russian-River-Brewing-Co",
"SAIGON LAGER" 						=> "Saigon-Brewery",
"SAM ADAMS" 						=> "Boston-Beer-Co",
"SAM SMITH" 						=> "Samuel-Smiths-Brewery",
"SAMILCLAUS" 						=> "Eggenberger-Brewery",
"SAN LUCAS" 						=> "Cerveceria-La-Constancia-SA",
"SAPPORO" 							=> "Sapporo-Breweries-Ltd",
"SCALDIS" 							=> "Brasserie-Dubuisson",
"SCHNEIDER" 						=> "Schneider-Brewery",
"SCUTTLEBUTT",						=> "Scuttlebutt-Brewing-Co-Everett",
"SEA DOG" 							=> "Sea-Dog-Brewing-Co",
"SHARP NA" 							=> "SABMiller",
"SHEAF STOUT" 						=> "Carlton-and-United-Breweris",
"SHIPYARD"						 	=> "Shipyard-Brewery",
"SIERRA NEVADA" 					=> "Sierra-Nevada-Brewing-Co",
"SILETZ" 							=> "Siletz-Brewing",
"SKAGIT RIVER" 						=> "Skagit-River-Brewing-Co",
"SMITHWICK" 						=> "Smithwicks-Brewery",
"SNIPES MOUNTAIN" 					=> "Snipes-Mountain-Brewing-Inc",
"SOL" 								=> "Cuauhtémoc-Moctezuma-Brewery",
"SOUTHERN TIER" 					=> "Southern-Tier-Brewing-Co",
"SPANISH PEAKS" 					=> "Spanish-Peaks-Brewing-Micro",
"SPATEN" 							=> "Spaten-Brewery",
"ST FEUILLIEN" 						=> "Brewery-St-Feuillien",
"ST LOUIS" 							=> "Brouwerij-Van-Honsebrouck",
"ST PAULI GIRL" 					=> "St-Pauli-Brewery",
"ST PETER'S" 						=> "St-Peters-Brewery",
"STEEL RESERVE" 					=> "SABMiller",
"STEINLAGER" 						=> "Lion-Nathan",
"STONE BREWING CO.",				=> "Stone-Brewing-Company",
"STONE" 							=> "Stone-Brewing-Company",
"TANNERS JACK" 						=> "Greene-King-Morland-Brewery",
"TECATE" 							=> "Cuauhtémoc-Moctezuma-Brewery",
"TETLEY" 							=> "Tetleys-Brewery",
"TRACKTOWN" 						=> "Eugene-City-Brewery",
"TRAFALGAR" 						=> "Trafalgar-Brewing-Company",
"TRAQUAIR" 							=> "Traquair-House-Brewery",
"TRUMER" 							=> "Trumer-Brauerei",
"TSINGTAO" 							=> "Tsingtao-Brewery",
"UNIBROUE" 							=> "Brasserie-Unibroue-Brewery",
"URTHEL" 							=> "Urthel-Brewery",
"VELTINS" 							=> "Brauerei-Veltins",
"VICTORY" 							=> "Victory-Brewing-Co-Downingtown",
"WALKING MAN" 						=> "Walking-Man-Brewing",
"WEIHENSTEPHAN" 					=> "Weihenstephan-Brewery",
"WELLS" 							=> "Charles-Wells-Brewery",
"WESTMALLE TRAPPIST" 				=> "Brouwerij-der-Trappisten-van-Westmalle",
"WEXFORD" 							=> "Greene-King-Morland-Brewery",
"WINGWALKER" 						=> "City-Brewery",
"WOLAVERS" 							=> "Wolavers-Organic-Brewing",
"WYCHWOOD" 							=> "Wychwood-Brewery",
"XINGU" 							=> "Servejaria-Sul-Brasileira",
"YOUNGS" 							=> "Young-and-Co-Brewery-Plc",
"ZATEC" 							=> "Zatec-Brewery",
);


my $last_beer_name="";
my %beer_info=();



sub output_beer
{
	my $attribs="";
	
	my $brewery_id="";
	if (defined($brewery_map{$beer_info{brewer}}))
	{
		$brewery_id=$brewery_map{$beer_info{brewer}};
	}
	else
	{
		$brewery_id="UNKNOWN";
	}

	# Make beer id (brewery_id+beer_name)
	my $beer_id=$beer_info{beer_name};
	$beer_id=~s/^ //;
	$beer_id=~s/ $//;
	$beer_id=~s/[^a-z0-9]+/-/gi;
	$beer_id=~s/^-+//;
	$beer_id=~s/-+$//;
	$beer_id=$brewery_id."/".$beer_id;

	$beer_info{calories}=~s/\s+//g;
	$beer_info{calories_serving_size}=~s/^\s+//;
	$beer_info{calories_serving_size}=~s/\s+$//;
	if (length($beer_info{calories}) && length($beer_info{calories_serving_size}))
	{
		if ($beer_info{calories_serving_size}=~/(\d+)\s*oz/)
		{
			# 1 fluid ounce=0.02957353L=29.57353ml
			my $calories_per_ml=((1/$1)*$beer_info{calories})/29.57353;
			$attribs.=" calories_per_ml=\"$calories_per_ml\"";
		}
	}

	# ABV
	$beer_info{ABV}=~s/\s+//g;
	$beer_info{ABV}=~s/%+$//;
	if ($beer_info{ABV}=~/[\d\.]+/)
	{
		$attribs.=" abv=\"$beer_info{ABV}\"";
	}

	$beer_info{OG}=~s/\s+//g;
	if ($beer_info{OG}=~/[\d\.]+/)
	{
		$attribs.=" og=\"$beer_info{OG}\"";
	}

	$beer_info{FG}=~s/\s+//g;
	if ($beer_info{FG}=~/[\d\.]+/)
	{
		$attribs.=" fg=\"$beer_info{FG}\"";
	}
	
	$beer_info{IBU}=~s/\s+//g;
	if ($beer_info{IBU}=~/[\d\.]+/)
	{
		$attribs.=" ibu=\"$beer_info{IBU}\"";
	}
	
	$beer_info{ingredients}=~s/^\s+//;
	$beer_info{ingredients}=~s/\s+$//;
	$beer_info{ingredients}=~s/\s+/ /g;

	$beer_info{grains}=~s/^\s+//;
	$beer_info{grains}=~s/\s+$//;
	$beer_info{grains}=~s/\s+/ /g;

	$beer_info{hops}=~s/^\s+//;
	$beer_info{hops}=~s/\s+$//;
	$beer_info{hops}=~s/\s+/ /g;

	$beer_info{yeast}=~s/^\s+//;
	$beer_info{yeast}=~s/\s+$//;
	$beer_info{yeast}=~s/\s+/ /g;

	$beer_info{otherings}=~s/^\s+//;
	$beer_info{otherings}=~s/\s+$//;
	$beer_info{otherings}=~s/\s+/ /g;

	# UTF-8-encode text fields
	HTML::Entities::encode_entities_numeric($beer_id);
	HTML::Entities::encode_entities_numeric($brewery_id);
	HTML::Entities::encode_entities_numeric($beer_info{beer_name});
	HTML::Entities::encode_entities_numeric($beer_info{description});
	HTML::Entities::encode_entities_numeric($beer_info{ingredients});
	HTML::Entities::encode_entities_numeric($beer_info{grains});
	HTML::Entities::encode_entities_numeric($beer_info{hops});
	HTML::Entities::encode_entities_numeric($beer_info{yeast});
	HTML::Entities::encode_entities_numeric($beer_info{otherings});
	
	utf8::encode($beer_id);
	utf8::encode($brewery_id);
	utf8::encode($beer_info{beer_name});
	utf8::encode($beer_info{description});
	utf8::encode($beer_info{ingredients});
	utf8::encode($beer_info{grains});
	utf8::encode($beer_info{hops});
	utf8::encode($beer_info{yeast});
	utf8::encode($beer_info{otherings});
	
	print <<EOF;
<beer id="$beer_id" brewery_id="$brewery_id"$attribs>
	<name>$beer_info{beer_name}</name>
	<description>$beer_info{description}</description>
	<availability>$beer_info{availability}</availability>
	<ingredients>$beer_info{ingredients}</ingredients>
	<grains>$beer_info{grains}</grains>
	<hops>$beer_info{hops}</hops>
	<yeast>$beer_info{yeast}</yeast>
	<otherings>$beer_info{otherings}</otherings>
EOF

	$beer_info{style}=~s/^\s+//;
	$beer_info{style}=~s/\s+$//;
	$beer_info{style}=~s/\s+/ /;

	# There can be multiple styles, separated by commas
	my @styles=();
	my @ss=split(/\s*,\s*/,$beer_info{style});
	foreach my $s (@ss)
	{
		$s=~tr/a-z/A-Z/; # Convert to uppercase
		if (length($s) && defined($styles_map{$s}))
		{
			push(@styles,$styles_map{$s});
		}
	}

	if ($#styles>=0)
	{
		print <<EOF;
	<styles>
EOF
		foreach my $s (@styles) {
			print <<EOF;
		<style bjcp_style_id="$s">$bjcp_styles{$s}</style>
EOF
		}
		
		print <<EOF;
	</styles>
EOF
	}
	
	if ($#{$beer_info{bottle_sizes}}>=0)
	{
		print <<EOF;
	<sizes>
EOF
	
		foreach my $h (@{$beer_info{bottle_sizes}})
		{
			my $attribs="";
			$h->{upc}=~s/\s+//g;
			# TODO: fix UPCs without hyphens to add hyphens
			if ($h->{upc}=~/[\d-]+/ && $h->{upc}!~/0-00000-00000-0/)
			{
				$attribs.=" upc=\"$h->{upc}\"";
			}
		
			print <<EOF;
		<size$attribs>
			<description>$h->{size}</description>
			<distributor>
				<name>$h->{distributor}</name>
				<item>$h->{item}</item>
				<reg_price>$h->{reg_price}</reg_price>
				<post_off>$h->{post_off}</post_off>
				<net_case_price>$h->{net_case_price}</net_case_price>
				<unit_price>$h->{unit_price}</unit_price>
				<deposit>$h->{deposit}</deposit>
			</distributor>
		</size>
EOF
	
		}


		print <<EOF;
</sizes>
EOF

	}
	
	print <<EOF;
</beer>
EOF

}

print <<EOF;
<?xml version="1.0" encoding="UTF-8"?>
<beers>
EOF


while (<>)
{
	my @cols=split(/\t/);
	
	my ($brewer,$size,$beer_name,$style,$description,$ABV,$OG,$FG,$IBU,$availability,
		$calories,$calories_serving_size,$ingredients,$grains,$hops,$yeast,$otherings,
		$distributor,$item,$upc,$reg_price,$post_off,$net_case_price,$unit_price,$deposit,$post_off_unit)=split(/\t/);
		
	$post_off="" 		if (!defined($post_off));
	$net_case_price="" 	if (!defined($net_case_price));
	$unit_price="" 		if (!defined($unit_price));
	$deposit="" 		if (!defined($deposit));
	$post_off_unit="" 	if (!defined($post_off_unit));
	
	# Uppercase brewery name
	$brewer=~tr/a-z/A-Z/;
	
	# Ignore blank beer names
	$beer_name=~s/\s+/ /;
	$beer_name=~s/^\s+//;
	$beer_name=~s/\s+$//;
	if (!defined($brewery_map{$brewer}) || !length($brewery_map{$brewer}))
	{
		print STDERR "Unknown brewer:$brewer\n";
	}
	elsif (length($beer_name) && defined($brewery_map{$brewer}))
	{
		if ($last_beer_name ne $beer_name)
		{
			# Output the beer info
			if (length($last_beer_name))
			{
				output_beer();
			}
	
			# Start new beer info
			%beer_info=(
				brewer => $brewer,
				beer_name => $beer_name,
				style => $style,
				description => $description,
				ABV => $ABV,
				OG => $OG,
				FG => $FG,
				IBU => $IBU,
				availability => $availability,
				calories => $calories,
				calories_serving_size => $calories_serving_size,
				ingredients => $ingredients,
				grains  => $grains,
				hops	=> $hops,
				yeast	=> $yeast,
				otherings => $otherings,
				bottle_sizes => []
			);
		
			$last_beer_name=$beer_name;
		}
	
		my %h=(
			distributor => $distributor,
			size => $size,
			item => $item,
			upc => $upc,
			reg_price => $reg_price,
			post_off  => $post_off,
			net_case_price => $net_case_price,
			unit_price => $unit_price,
			deposit => $deposit,
		);
		push(@{$beer_info{bottle_sizes}},\%h);
	}
		
}

if (defined($beer_info{beer_name}) && length($beer_info{beer_name}))
{
	output_beer();
}

print <<EOF;
</beers>
EOF
