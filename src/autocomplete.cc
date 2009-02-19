#include <fcgi_stdio.h>
extern "C"
{
#include <cgic.h>
}

#include <stdlib.h>
#include <string.h>

using namespace std;

const char* brewery_names[]=
{
	"Dogfish Head",
	"Elliott Bay Brewery Pub",
	"Maritime Pacific Brewing",
	"my new brewery",
	"North Coast Brewing Co.",
	"Russian River Brewing Company",
	"Sierra Nevada Brewing Co.",
	"Stone Brewing Co.",
};

const char* beer_styles[]=
{
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
};

extern "C" void cgiInit() 
{
	/* TODO: Load brewery list into memory, it *must* be sorted */
	/* TODO: Load style list into memory, it *must* be sorted */
}

extern "C" void cgiUninit() 
{
	/* TODO: Free brewery list from memory */
	/* TODO: Free style list from memory */
}

void autocomplete(const char* query,size_t query_len,const char** list,size_t count)
{
	// Binary-search brewery_names
	size_t hi=count;
	size_t lo=hi>0?1:hi;
	// lo and hi are 1-based so that we can decrement lo to zero without wrapping around
	while (lo<=hi)
	{
		size_t mid=(hi+lo)/2;
		// Remember, mid is 1-based, so use mid-1 to reference array items
		int cmp=strncasecmp(query,list[mid-1],query_len);
		if (cmp<0)
		{
			hi=mid-1;
		}
		else if (cmp>0)
		{
			lo=mid+1;
		}
		else
		{
			// Match, go backwards until we find the first one that doesn't match
			do
			{
				--mid;
			}
			while (mid && strncasecmp(query,list[mid-1],query_len) == 0);
			
			// mid is now before the 1st that matches, so spit out the names until it no longer matches
			do
			{
				++mid;
				if (strncasecmp(query,list[mid-1],query_len)==0)
					printf("%s\n",list[mid-1]);
			}
			while (mid<count);
			break;
		}
	}
}

int cgiMain()
{
	cgiHeaderContentType((char*)"text/plain");
	
	char query[256];
	char dataset[32];
	
	cgiFormString((char*)"q",query,sizeof(query));
	cgiFormString((char*)"dataset",dataset,sizeof(dataset));
	
	size_t query_len=strlen(query);
	
	if (!strlen(dataset) || !strcasecmp(dataset,"brewery"))
	{
		autocomplete(query,query_len,brewery_names,sizeof(brewery_names)/sizeof(brewery_names[0]));
	}
	else if (!strcasecmp(dataset,"bjcp_style"))
	{
		autocomplete(query,query_len,beer_styles,sizeof(beer_styles)/sizeof(beer_styles[0]));
	}
	
	return 0;
}
