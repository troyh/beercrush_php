#define NO_FCGI_DEFINES
#include <fcgi_stdio.h>

extern "C"
{
#include <cgic.h>
}

#include <map>
#include <string>
#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fstream>

// #include <boost/filesystem.hpp>

using namespace std;

const char* const dataFilename="/var/local/BeerCrush/meta/autocomplete_names.tsv";

// Searchable data structures
const char** searchable_names=NULL;
// typedef enum { UNKNOWN=0, BEER=1, BREWERY=2, PLACE=4, STYLE=128 } TYPES;
// TYPES *searchable_types=NULL;
size_t searchable_names_count=0;

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

bool readFile(const char* fname, size_t* count, const char*** names)
{
	char* buf=0;
	const char** list=0;
	size_t entries=0;
	
	struct stat statbuf;
	stat(fname,&statbuf);
	// TODO: use the file's time to determine whether to load the file again

	// Read the entire file into memory
	FILE* f=fopen(fname,"r");
	if (f)
	{
		buf=new char[statbuf.st_size+1];
		if (buf)
		{
			size_t n=fread(buf,sizeof(buf[0]),statbuf.st_size,f);
			buf[n]='\0';
		}
		
		fclose(f);
	}
	
	if (buf)
	{
		// Walk buf and count the newlines
		for (char* p=strchr(buf,'\n');p;p=strchr(p+1,'\n'))
		{
			*p='\0'; // change the newline to a null-terminator
			++entries;
		}
		
		if (entries)
		{
			list=new const char*[entries];
			
			char* p=buf;
			for(size_t i = 0; i < entries; ++i)
			{
				list[i]=p;
				for (p+=strlen(p)+1;*p=='\0';++p)
				{ // Skip to next line, just in case there's multiple null-terminators at the end
				}
			}
		}
	}
	
	*count=entries;

	*names=list;
}


extern "C" void cgiInit() 
{
	// TODO: make it read from the config file and load the data straight from couchdb so the file location is not hardcoded
	// // Read the conf file
	// Config cfg("/etc/BeerCrush/BeerCrush.conf");
	// 
	// char fname[256];
	// strncpy(fname,cfg.get("DOC_DIR"),sizeof(fname));
	// fname[sizeof(fname)-1]='\0';
	// strncat(fname,"/meta/brewery/autocomplete_names.txt",sizeof(fname)-strlen(fname)-1);
	// fname[sizeof(fname)-1]='\0';
	
	/* Load brewery list into memory, it *must* be sorted */
	readFile(dataFilename,&searchable_names_count,&searchable_names);
}

extern "C" void cgiUninit() 
{
	if (searchable_names)
		free(searchable_names);

	/* TODO: Free style list from memory */
}

void autocomplete(const char* query,size_t query_len,const char** list,size_t count, bool bXMLOutput)
{
	if (count==0)
		return;
		
	// Binary-search list
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
				{
					if (bXMLOutput)
					{
						// TODO: use libxml2 to take care of XML entities
						FCGI_printf("<result>");
						FCGI_printf("<text>%s</text>",list[mid-1]);
						FCGI_printf("</result>");
					}
					else
					{
						FCGI_printf("%s\n",list[mid-1]);
					}
				}
				else
					break;
			}
			while (mid<count);
			break;
		}
	}
}

int cgiMain()
{
	// See if we should refresh the data (older than 1 hour)
	// readFile(dataFilename,&searchable_names_count,&searchable_names,&searchable_types);
		
	bool bXMLOutput=false;
	
	char query[256];
	char dataset[32];
	char output[16];
	char types[16];
	
	cgiFormString((char*)"q",query,sizeof(query));
	cgiFormString((char*)"dataset",dataset,sizeof(dataset));
	cgiFormString((char*)"output",output,sizeof(output));

	if (!strcasecmp(output,"xml"))
		bXMLOutput=true;
		
	if (bXMLOutput)
	{
		cgiHeaderContentType((char*)"text/xml");
		FCGI_printf("<results>");
	}
	else
		cgiHeaderContentType((char*)"text/plain");

	size_t query_len=strlen(query);

	if (!strcasecmp(dataset,"bjcp_style"))
	{
		autocomplete(query,query_len,beer_styles,sizeof(beer_styles)/sizeof(beer_styles[0]),bXMLOutput);
	}
	else
	{
		autocomplete(query,query_len,searchable_names,searchable_names_count,bXMLOutput);
	}
	
	if (bXMLOutput)
		FCGI_printf("</results>");
	
	
	return 0;
}
