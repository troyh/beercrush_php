#include <fcgiapp.h>

extern "C"
{
#include "../src/external/cgic/cgic.h"
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




using namespace std;

const char* const dataFilename="/var/local/BeerCrush/meta/autocomplete_db.tsv";
static time_t datafile_last_read=0;

// Searchable data structures
const char** searchable_names=NULL;
typedef enum { UNKNOWN=0, BEER=1, BREWERY=2, PLACE=4, STYLE=128 } TYPES;
TYPES *searchable_types=NULL;
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

bool readFile(const char* fname, size_t* count, const char*** names, TYPES** types)
{
	char* buf=0;
	const char** list=0;
	TYPES* list_types=0;
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
			list_types=new TYPES[entries];
			
			char* p=buf;
			for(size_t i = 0; i < entries; ++i)
			{
				list[i]=p;
				char* tab=strchr(p,'\t');
				
				for (p+=strlen(p)+1;*p=='\0';++p)
				{ // Skip to next line, just in case there's multiple null-terminators at the end
				}
				
				if (tab)
				{
					*tab='\0';
					++tab;
					if (!strcasecmp(tab,"beer"))
						list_types[i]=BEER;
					else if (!strcasecmp(tab,"brewery"))
						list_types[i]=BREWERY;
					else if (!strcasecmp(tab,"place"))
						list_types[i]=PLACE;
				}
				else
					list_types[i]=UNKNOWN;
				
			}
		}
	}
	
	*count=entries;
	*names=list;
	*types=list_types;
	
	datafile_last_read=time(0);
}


extern "C" void fcgiInit() 
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
	readFile(dataFilename,&searchable_names_count,&searchable_names,&searchable_types);
}

extern "C" void fcgiUninit() 
{
	if (searchable_names)
		free(searchable_names);

	/* TODO: Free style list from memory */
}

void autocomplete(FCGX_Stream* out, const char* query,size_t query_len,const char** list,TYPES* types, size_t count, int filtertype, bool bXMLOutput)
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

			size_t limit=30; // Limit it to 30 results, more than that is unnecessary
			do
			{
				++mid;
				if (limit && (strncasecmp(query,list[mid-1],query_len)==0))
				{
					if (filtertype==0 || !types || (types[mid-1]&filtertype))
					{
						if (bXMLOutput)
						{
							// TODO: use libxml2 to take care of XML entities
							FCGX_FPrintF(out,"<result>");
							FCGX_FPrintF(out,"<text>%s</text>",list[mid-1]);
							FCGX_FPrintF(out,"</result>");
						}
						else
						{
							FCGX_FPrintF(out,"%s\n",list[mid-1]);
						}
						
						--limit;
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

extern "C" int fcgiMain(FCGX_Stream *in,FCGX_Stream *out,FCGX_Stream *err,FCGX_ParamArray envp)
{
	// See if we should refresh the data (older than 1 hour)
	if (datafile_last_read < (time(0)-(60*60)))
		readFile(dataFilename,&searchable_names_count,&searchable_names,&searchable_types);
	
	bool bXMLOutput=false;

	char query[256]="";
	char dataset[32]="";
	char output[16]="";
	char types[16]="";

	cgiFormString("q",query,sizeof(query));
	cgiFormString("dataset",dataset,sizeof(dataset));
	cgiFormString("output",output,sizeof(output));

	int filtertype=0;
	if (*dataset)
	{
		if (!strcasecmp(dataset,"beers"))
			filtertype=BEER;
		else if (!strcasecmp(dataset,"breweries"))
			filtertype=BREWERY;
		else if (!strcasecmp(dataset,"places"))
			filtertype=PLACE;
		else if (!strcasecmp(dataset,"beersandbreweries"))
			filtertype=BEER|BREWERY;
	}

	if (!strcasecmp(output,"xml"))
		bXMLOutput=true;
	
	if (bXMLOutput)
	{
		FCGX_FPrintF(out,"Content-Type: text/xml; charset=utf-8\r\n\r\n");
		FCGX_FPrintF(out,"<results>");
	}
	else
		FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");

	size_t query_len=strlen(query);

	if (!strcasecmp(dataset,"bjcp_style"))
	{
		autocomplete(out,query,query_len,beer_styles,0,sizeof(beer_styles)/sizeof(beer_styles[0]),filtertype,bXMLOutput);
	}
	else
	{
		autocomplete(out,query,query_len,searchable_names,searchable_types,searchable_names_count,filtertype,bXMLOutput);
	}

	if (bXMLOutput)
		FCGX_FPrintF(out,"</results>");
	
	return 0;
}

