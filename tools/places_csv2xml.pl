#!/usr/bin/perl -w

# Columns:
#
# 1 place type
# 2 in operation
# 3 specializes in beer
# 4 name
# 5 address
# 6 city
# 7 state/province
# 8 zip
# 9 country
# 10 neighborhood
# 11 phone
# 12 URL
# 13 Description
# 14 established 
# 15 tied or free
# 16 open hours
# 17 bottled beer to go
# 18 growlers to go
# 19 kegs to go
# 20 brew on premises
# 21 taps
# 22 casks
# 23 bottles
# 24 tours
# 25 tour info
# 26 tour hours
# 27 gift shop
# 28 tasting room
# 29 tasting room hours
# 30 food
# 31 meals served
# 32 food description
# 33 menu?
# 34 price range
# 35 attire
# 36 reservations
# 37 Wheelchair accessible
# 38 music
# 39 wi-fi
# 40 alcohol
# 41 accepts credit cards
# 42 good for groups
# 43 outdoor seating
# 44 smoking
# 45 parking
# 46 kid-friendly
# 47 waiter service


use utf8;
use HTML::Entities qw(encode_entities_numeric);

sub xmlencode
{
	my $s=shift;
	HTML::Entities::encode_entities_numeric($s);
	utf8::encode($s);
	return $s;
}


while (<>)
{
	my @cols=split(/\t/);
	
	my ($place_type,$in_operation,$specializes_in_beer,$name,$address,$city,$state_province,$zip,$country,$neighborhood,$phone,
		$URL,$Description,$established,$tied_or_free,$open_hours,$bottled_beer_to_go,$growlers_to_go,$kegs_to_go,$brew_on_premises,
		$taps,$casks,$bottles,$tours,$tour_info,$tour_hours,$gift_shop,$tasting_room,$tasting_room_hours,$food,$meals_served,
		$food_description,$menu,$price_range,$attire,$reservations,$Wheelchair_accessible,$music,$wifi,$alcohol,$accepts_credit_cards,
		$good_for_groups,$outdoor_seating,$smoking,$parking,$kid_friendly,$waiter_service)=split(/\t/);

	$Description				="" if (!defined($Description			    ));
	$URL						="" if (!defined($URL					    ));
	$Wheelchair_accessible		="" if (!defined($Wheelchair_accessible	    ));
	$accepts_credit_cards		="" if (!defined($accepts_credit_cards	    ));
	$address					="" if (!defined($address				    ));
	$alcohol					="" if (!defined($alcohol				    ));
	$attire						="" if (!defined($attire					));
	$bottled_beer_to_go			="" if (!defined($bottled_beer_to_go		));
	$bottles					="" if (!defined($bottles				    ));
	$brew_on_premises			="" if (!defined($brew_on_premises		    ));
	$casks						="" if (!defined($casks					    ));
	$city						="" if (!defined($city					    ));
	$country					="" if (!defined($country				    ));
	$established				="" if (!defined($established			    ));
	$food						="" if (!defined($food					    ));
	$food_description			="" if (!defined($food_description		    ));
	$gift_shop					="" if (!defined($gift_shop				    ));
	$good_for_groups			="" if (!defined($good_for_groups		    ));
	$growlers_to_go				="" if (!defined($growlers_to_go			));
	$in_operation				="" if (!defined($in_operation			    ));
	$kegs_to_go					="" if (!defined($kegs_to_go				));
	$kid_friendly				="" if (!defined($kid_friendly			    ));
	$meals_served				="" if (!defined($meals_served			    ));
	$menu						="" if (!defined($menu					    ));
	$music						="" if (!defined($music					    ));
	$name						="" if (!defined($name					    ));
	$neighborhood				="" if (!defined($neighborhood			    ));
	$open_hours					="" if (!defined($open_hours				));
	$outdoor_seating			="" if (!defined($outdoor_seating		    ));
	$parking					="" if (!defined($parking				    ));
	$phone						="" if (!defined($phone					    ));
	$place_type					="" if (!defined($place_type				));
	$price_range				="" if (!defined($price_range			    ));
	$reservations				="" if (!defined($reservations			    ));
	$smoking					="" if (!defined($smoking				    ));
	$specializes_in_beer		="" if (!defined($specializes_in_beer	    ));
	$state_province				="" if (!defined($state_province			));
	$taps						="" if (!defined($taps					    ));
	$tasting_room				="" if (!defined($tasting_room			    ));
	$tasting_room_hours			="" if (!defined($tasting_room_hours		));
	$tied_or_free				="" if (!defined($tied_or_free			    ));
	$tour_hours					="" if (!defined($tour_hours				));
	$tour_info					="" if (!defined($tour_info				    ));
	$tours						="" if (!defined($tours					    ));
	$waiter_service				="" if (!defined($waiter_service			));
	$wifi						="" if (!defined($wifi					    ));
	$zip						="" if (!defined($zip					    ));
	
	if ($in_operation eq "open") { $in_operation="yes"; }
	else 						 { $in_operation="no"; }

	if ($specializes_in_beer eq "yes") { $specializes_in_beer="yes"; }
	else 						 { $specializes_in_beer="no"; }

	if ($tied_or_free eq "tied") { $tied_or_free="yes"; }
	else 						 { $tied_or_free="no"; }

	if ($bottled_beer_to_go eq "yes") { $bottled_beer_to_go="yes"; }
	else 						 { $bottled_beer_to_go="no"; }

	if ($growlers_to_go eq "yes") { $growlers_to_go="yes"; }
	else 						 { $growlers_to_go="no"; }

	if ($kegs_to_go eq "yes") { $kegs_to_go="yes"; }
	else 						 { $kegs_to_go="no"; }

	if ($brew_on_premises eq "yes") { $brew_on_premises="yes"; }
	else 						 { $brew_on_premises="no"; }

	# Compute an id
	my $id="$name $city $state_province";
	$id=~s/['\.]//g;
	$id=~s/ & / and /g;
	$id=~s/[^a-zA-Z0-9]/-/g;
	$id=~s/-+/-/g;
	$id=~s/^-+//g;
	$id=~s/-+$//g;

	# Get a unique ID based on whether the file for the doc exists yet
	if (-e "xml/$id.xml")
	{
		$n=2;
		while (-e "xml/$id-$n.xml")
		{
			$n++;
		}
		$id.="-$n";
	}
	
	open(OUT,">xml/$id.xml");
	
	$name          =xmlencode($name);
	$address       =xmlencode($address);
	$city          =xmlencode($city);
	$state_province=xmlencode($state_province);
	$zip           =xmlencode($zip);
	$country       =xmlencode($country);
	$neighborhood  =xmlencode($neighborhood);
	$Description   =xmlencode($Description);
	$open_hours    =xmlencode($open_hours);
	$tour_info     =xmlencode($tour_info);
	
	
	print OUT <<EOF;
<place id="$id" in_operation="$in_operation" specializes_in_beer="$specializes_in_beer" tied="$tied_or_free" bottled_beer_to_go="$bottled_beer_to_go" growlers_to_go="$growlers_to_go" kegs_to_go="$kegs_to_go" brew_on_premises="$brew_on_premises" taps="$taps" casks="$casks" bottles="$bottles" wheelchair_accessible="$Wheelchair_accessible" music="$music" wifi="$wifi">
	<type>$place_type</type>
	<name>$name</name>
	<description>$Description</description>
	<phone>$phone</phone>
	<uri>$URL</uri>
	<established>$established</established>
	<address>
		<street>$address</street>
		<city>$city</city>
		<state>$state_province</state>
		<zip>$zip</zip>
		<country>$country</country>
		<neighborhood>$neighborhood</neighborhood>
	</address>
	<hours>
		<open>$open_hours</open>
		<tour>$tour_hours</tour>
		<tasting>$tasting_room_hours</tasting>
	</hours>
	<tour_info>$tour_info</tour_info>
	<restaurant reservations="$reservations" alcohol="$alcohol" accepts_credit_cards="$accepts_credit_cards" good_for_groups="$good_for_groups" outdoor_seating="$outdoor_seating" smoking="$smoking">
		<food_description>$food_description</food_description>
		<menu_uri>$menu</menu_uri>
		<price_range>$price_range</price_range>
		<attire>$attire</attire>
		<waiter_service>$waiter_service</waiter_service>
	</restaurant>
	<parking>$parking</parking>
	<kid_friendly>$kid_friendly</kid_friendly>
</place>
EOF

	close OUT;

}

