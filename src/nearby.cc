#include <fcgiapp.h>
#define __STDC_LIMIT_MACROS
extern "C"
{
#include "external/cgic/cgic.h"
}
#include <stdlib.h>
#include <math.h>
#include <fstream>
#include <string.h>
#include <ctype.h>
#include <stdint.h>

#define MAX_PLACE_ID_LEN 256

static time_t datafile_last_read=0;

// NOTE: the values should be bit numbers, not just sequential values
enum PLACETYPE {
	PLACETYPE_UNKNOWN=0,
	PLACETYPE_BAR=1,
	PLACETYPE_BREWPUB=2,
	PLACETYPE_RESTAURANT=4,
	PLACETYPE_STORE=8,
	PLACETYPE_BREWERY=16
};

// const char* placetype_strings[]={
// 	"UNKNOWN",
// 	"BREWPUB",
// 	"STORE",
// 	"BAR",
// 	"RESTAURANT",
// };

struct LATLONPAIR
{
	double lat;
	double lon;
	char place_id[MAX_PLACE_ID_LEN];
	char* name;
	PLACETYPE placetype;
};

LATLONPAIR* latlonpairs=0;
size_t latlonpairs_count=0;

using namespace std;

extern "C" void fcgiInit() 
{
	// Init list of latitude/longitude pairs values, sorted by latitude
	// TODO: use shared memory so all processes don't duplicate the data
	
	// To make the list, use the script in scripts/nearby/latlonfile:
	char buf[256];
		
	size_t lines=0;
	ifstream f("/var/local/BeerCrush/meta/latlonpairs.txt");
	while (f.good())
	{
		f.getline(buf,sizeof(buf));
		if (f.good())
		{
			++lines;
		}
	}
	
	f.clear();
	f.seekg(0,ios::beg); // Rewind to beginning
	
	if (f.good())
	{
		latlonpairs=(LATLONPAIR*)calloc(lines,sizeof(*latlonpairs));
		if (latlonpairs)
		{
			size_t n=0;
			while (f.good())
			{
				f.getline(buf,sizeof(buf));
				buf[sizeof(buf)-1]='\0';
				if (f.good())
				{
					char* p=buf;
					latlonpairs[n].lat=strtod(p,NULL);
					p=strchr(p,'\t');
					if (p)
					{
						for (++p;isspace(*p) && *p;++p) {}
						latlonpairs[n].lon=strtod(p,NULL);
						p=strchr(p,'\t'); // Skip to id
						if (p)
						{
							for (++p;isspace(*p) && *p;++p) {}
							strncpy(latlonpairs[n].place_id, p, sizeof(latlonpairs[n].place_id)-1);
							latlonpairs[n].place_id[sizeof(latlonpairs[n].place_id)-1]='\0';
							
							p=strchr(latlonpairs[n].place_id,'\t'); // Skip to name
							if (p)
							{
								*p='\0'; // null-terminate id
								for (++p;isspace(*p) && *p;++p) {}
								latlonpairs[n].name=p;

								p=strchr(latlonpairs[n].name,'\t'); // Skip to placetype
								if (p)
								{
									*p='\0'; // null-terminate name
									for (++p;isspace(*p) && *p;++p) {}
									if (!strcasecmp(p,"Brewpub"))
										latlonpairs[n].placetype=PLACETYPE_BREWPUB;
									else if (!strcasecmp(p,"Store"))
										latlonpairs[n].placetype=PLACETYPE_STORE;
									else if (!strcasecmp(p,"Bar"))
										latlonpairs[n].placetype=PLACETYPE_BAR;
									else if (!strcasecmp(p,"Restaurant"))
										latlonpairs[n].placetype=PLACETYPE_RESTAURANT;
									else if (!strcasecmp(p,"Brewery"))
										latlonpairs[n].placetype=PLACETYPE_BREWERY;
									else
										latlonpairs[n].placetype=PLACETYPE_UNKNOWN;
								}
							}
							else
							{
								latlonpairs[n].name=latlonpairs[n].place_id; // Better than nothing
							}
						}
					}
				
					++n;
				}
			}
		
			latlonpairs_count=n;
		}
	}
	
	datafile_last_read=time(0);
}

extern "C" void fcgiUninit() 
{
	// Free list of lat/lon pairs
	if (latlonpairs)
		free(latlonpairs);
}

size_t binary_search(double lat)
{
	// Binary-search latlonpairs
	size_t lo=0,hi=latlonpairs_count,mid;
	
	while (lo<hi)
	{
		mid=(hi+lo)/2;
		if (latlonpairs[mid].lat < lat)
			lo=mid+1;
		else
			hi=mid-1;
	}
	
	return mid;
}


extern "C" int fcgiMain(FCGX_Stream *in,FCGX_Stream *out,FCGX_Stream *err,FCGX_ParamArray envp)
{
	// See if we should refresh the data (older than 1 hour)
	if (datafile_last_read < (time(0)-(60*60)))
		fcgiInit();

	char latstr[32];
	char lonstr[32];
	char withinstr[32];
	char types[32];
	
	cgiFormString((char*)"lat",latstr,sizeof(latstr));
	cgiFormString((char*)"lon",lonstr,sizeof(lonstr));
	cgiFormString((char*)"within",withinstr,sizeof(withinstr));
	cgiFormString((char*)"types",types,sizeof(types));
	
	size_t filter_types=atoi(types);
	if (filter_types==0)
		filter_types=UINT16_MAX;

	double lat=strtod(latstr,NULL);
	double lon=strtod(lonstr,NULL);
	double within=strtod(withinstr,NULL);
	
	if (!within)
		within=10;  // Default to 10 miles

	// Formula from https://answers.google.com/answers/threadview?id=577262
	double lon_deg_len=69.1703234283616 * cos(lat*0.0174532925199433);
	const double lat_deg_len=69.172; // 1 degree of latitude is 69.172 miles
	
	double lat_max=lat+(double)(within/lat_deg_len);
	double lat_min=lat-(double)(within/lat_deg_len);
	
	double lon_max=lon+(double)(within/lon_deg_len);
	double lon_min=lon-(double)(within/lon_deg_len);

	FCGX_FPrintF(out,"Content-Type: application/json; charset=utf-8\r\n\r\n");
	// cgiHeaderStatus(200,(char*)"OK");
	// FCGI_printf("LatDegLen: %f\nWithin: %f\n",lat_deg_len,within);
	// FCGI_printf("Lat: %f to %f\nLon: %f to %f\n",lat_min,lat_max,lon_min,lon_max);
	// FCGI_printf("LatLonPairs: %d\n",latlonpairs_count);
	// FCGI_printf("LatLonPairs: %p\n",latlonpairs);
	
	// Find places where the latitude is between lat_min & lat_max and the longitude is between lon_min & lon_max
	size_t min_idx=binary_search(lat_min);
	size_t max_idx=binary_search(lat_max);

	// Count them first
	size_t count=0;
	for(size_t i = min_idx; i < max_idx; ++i)
	{
		// Find each longitude value that is between lon_min & lon_max
		if ((latlonpairs[i].placetype&filter_types) && lon_min <= latlonpairs[i].lon && latlonpairs[i].lon <= lon_max)
		{
			++count;
		}
	}

	FCGX_FPrintF(out,"{ \"count\": %d, \"places\": [",count);
	if (count)
	{
		bool bFirst=true;
		
		// Repeat to output the XML doc
		for(size_t i = min_idx; i < max_idx; ++i)
		{
			// Find each longitude value that is between lon_min & lon_max
			// FCGI_printf("Potential loc:%f,%f\n",latlonpairs[i].lat,latlonpairs[i].lon);
			if ((latlonpairs[i].placetype&filter_types) && lon_min <= latlonpairs[i].lon && latlonpairs[i].lon <= lon_max)
			{
				// Found one!
				FCGX_FPrintF(out,"%c{ \"id\": \"%s\", \"lat\": %f, \"lon\": %f, \"name\": \"%s\", \"placetype\": %d }",
					(bFirst?' ':','),
					latlonpairs[i].place_id,
					latlonpairs[i].lat,
					latlonpairs[i].lon,
					latlonpairs[i].name,
					latlonpairs[i].placetype
				);
				bFirst=false;
			}
		}
	}
	FCGX_FPrintF(out,"]}\n");

	return 0;
}
