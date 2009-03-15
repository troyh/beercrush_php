#include <fcgi_stdio.h>
extern "C"
{
#include <cgic.h>
}

#include <stdlib.h>
#include <ctype.h>
#include <string.h>
#include <time.h>
#include <unistd.h>

#include <string>

// Boost stuff
#include <boost/filesystem.hpp>

// libxml2 stuff
#include <libxml/xmlmemory.h>
#include <libxml/parser.h>
#include <libxml/xmlwriter.h>
#include <libxml/xpath.h>

#include <OAK/oak.h>
#include <OAK/loginutils.h>

#include "beercrush.h"


using namespace std;
namespace bfs=boost::filesystem;

extern "C" void cgiInit() 
{
}

extern "C" void cgiUninit() 
{
}





bool validate_place_state(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	// We can't check every province in the world, so we just accept anything, even bad US state names (!)
	*useOrigVal=true;
	return true;
}

bool validate_place_price_range(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	*useOrigVal=true;
	return true;
}

bool validate_place_type(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	const char* acceptables[]=
	{
		"Bar",
		"Brewery",
		"Brewpub",
		"Restaurant",
		"Store",
	};
	
	// Binary search the list
	int lo=0,hi=sizeof(acceptables)/sizeof(acceptables[0]),mid;
	while (lo<hi)
	{
		mid=(lo+hi)/2;
		int c=strcasecmp(s,acceptables[mid]);
		if (c==0)
		{	// Copy the proper-cased version just to make sure
			*useOrigVal=false;
			strncpy(newVal,acceptables[mid],newValSize);
			return true;
		}
		else if (c<0)
			lo=mid+1;
		else
			hi=mid-1;
	}
	
	return false;
}



EDITABLE_FIELDS place_editable_fields[]=
{ // IMPORTANT: These must be sorted! They are searched with a binary search.
	{ "/place/@bottled_beer_to_go", 		EDITABLE_FIELDS::validate_yesno },
	{ "/place/@bottles", 					EDITABLE_FIELDS::validate_yesno },
	{ "/place/@brew_on_premises", 			EDITABLE_FIELDS::validate_yesno },
	{ "/place/@casks",						EDITABLE_FIELDS::validate_uinteger },
	{ "/place/@growlers_to_go", 			EDITABLE_FIELDS::validate_yesno },
	{ "/place/@in_operation", 				EDITABLE_FIELDS::validate_yesno },
	{ "/place/@kegs_to_go", 				EDITABLE_FIELDS::validate_yesno },
	{ "/place/@kid_friendly",				EDITABLE_FIELDS::validate_yesno },
	{ "/place/@music", 						EDITABLE_FIELDS::validate_yesno },
	{ "/place/@specializes_in_beer",		EDITABLE_FIELDS::validate_yesno },
	{ "/place/@taps",						EDITABLE_FIELDS::validate_uinteger },
	{ "/place/@tied", 						EDITABLE_FIELDS::validate_yesno },
	{ "/place/@wheelchair_accessible",		EDITABLE_FIELDS::validate_yesno },
	{ "/place/@wifi", 						EDITABLE_FIELDS::validate_yesno },
	{ "/place/address/city",				EDITABLE_FIELDS::validate_text },
	{ "/place/address/country",				EDITABLE_FIELDS::validate_text },
	{ "/place/address/latitude",			EDITABLE_FIELDS::validate_float },
	{ "/place/address/longitude",			EDITABLE_FIELDS::validate_float },
	{ "/place/address/neighborhood",		EDITABLE_FIELDS::validate_text },
	{ "/place/address/state",				validate_place_state },
	{ "/place/address/street",				EDITABLE_FIELDS::validate_text },
	{ "/place/address/zip",					EDITABLE_FIELDS::validate_uinteger },
	{ "/place/description",					EDITABLE_FIELDS::validate_text },
	{ "/place/established",					EDITABLE_FIELDS::validate_uinteger },
	{ "/place/hours/open",					EDITABLE_FIELDS::validate_text },
	{ "/place/hours/tasting",				EDITABLE_FIELDS::validate_text },
	{ "/place/hours/tour",					EDITABLE_FIELDS::validate_text },
	{ "/place/name",						EDITABLE_FIELDS::validate_text },
	{ "/place/parking",						EDITABLE_FIELDS::validate_text },
	{ "/place/phone",						EDITABLE_FIELDS::validate_phone },
	{ "/place/restaurant/attire",			EDITABLE_FIELDS::validate_text },
	{ "/place/restaurant/food_description",	EDITABLE_FIELDS::validate_text },
	{ "/place/restaurant/menu_uri",			EDITABLE_FIELDS::validate_uri },
	{ "/place/restaurant/price_range",		validate_place_price_range },
	{ "/place/restaurant/waiter_service", 	EDITABLE_FIELDS::validate_yesno },
	{ "/place/tour_info",					EDITABLE_FIELDS::validate_text },
	{ "/place/type",						validate_place_type },
	{ "/place/uri",							EDITABLE_FIELDS::validate_uri },
};

xmlXPathObjectPtr queryXPath(xmlDocPtr doc, const xmlChar* xpath)
{
	xmlXPathObjectPtr result=NULL; 
	
	xmlXPathContextPtr context=xmlXPathNewContext(doc); 
	if (context) 
	{ 
		result=xmlXPathEvalExpression(xpath, context); 
		
		xmlXPathFreeContext(context); 
		
		if (result && xmlXPathNodeSetIsEmpty(result->nodesetval))
		{ 
			xmlXPathFreeObject(result); 
			result=NULL; 
		} 
	}
	
	return result; 
	
}


int setValue(xmlDocPtr doc, const xmlChar* xpath, const xmlChar* value, bool bCreateIfNonexistent=false)
{
	FCGI_printf("setValue(%p,%s,%s)\n",doc,xpath,value);
	xmlXPathObjectPtr nodeset=queryXPath(doc,xpath);
	if (!nodeset)
	{
		if (bCreateIfNonexistent)
		{	// Add the element
			xmlNodePtr root=xmlDocGetRootElement(doc);
			xmlNewTextChild(root,NULL,xpath,value);
		}
	}
	else
	{
		// Update the element
		xmlNodeSetContent(nodeset->nodesetval->nodeTab[0],value);
		xmlXPathFreeObject(nodeset);
	}
	return 0;
}

int cgiMain()
{
	if (!userIsValidated())
	{
		cgiHeaderStatus(401,(char*)"User could not be validated");
		return 0;
	}
	
	try
	{
		cgiHeaderContentType((char*)"text/plain");
		
		char userid[MAX_USERID_LEN];
		cgiCookieString((char*)"userid",userid,sizeof(userid));

		FCGI_printf("userid:%s\n",userid);
		FCGI_printf("Path:%s\n",cgiPathInfo);
		if (!strcmp(cgiPathInfo,"/place"))
		{
			char place_id[BEERCRUSH_MAX_PLACE_ID_LEN];
			if (cgiFormString((char*)"place_id",place_id,sizeof(place_id))!=cgiFormSuccess)
				throw BeerCrushException("Invalid place_id");
			
			bfs::path place_filename("/home/troy/beerliberation/xml/place/");
			place_filename=place_filename / place_id;
			place_filename=bfs::change_extension(place_filename,".xml");
			
			FCGI_printf("XML doc:%s\n",place_filename.string().c_str());

			xmlDocPtr doc;
			doc=xmlParseFile(place_filename.string().c_str());
			if (!doc)
				throw BeerCrushException("Unable to open document");
		
			char content_type[256];
			cgiFormResultType res=cgiFormFileContentType((char*)"file",content_type,sizeof(content_type));
			if (res!=cgiFormNoContentType && res!=cgiFormNotFound)
				throw BeerCrushException("Unsupported content type");
				
			char** fields;
			if (cgiFormEntries(&fields)==cgiFormSuccess)
			{
				try
				{
					// Count the number of fields and bytes needed for the data
					for(size_t i = 0; fields[i]; ++i)
					{
						if (!strcmp(fields[i],"place_id")) // Ignore place_id here
							continue;
							
						char buf[256];
						char* bufptr=0;
				
						int n;
						cgiFormStringSpaceNeeded(fields[i],&n);
						if (n>sizeof(buf))
						{	// Alloc space for it
							bufptr=(char*)calloc(1,n);
							if (!bufptr)
								throw BeerCrushException("Internal error");
						}

						if (cgiFormString(fields[i],(bufptr?bufptr:buf),(bufptr?n:sizeof(buf)))!=cgiFormSuccess)
							throw BeerCrushException("CGI error");

						// fields[i] is an xpath string
						char xpath[256]="/place/";
						strncat(xpath,fields[i],sizeof(xpath)-strlen(xpath)-1);
						xpath[sizeof(xpath)-1]='\0';
						
						int field=EDITABLE_FIELDS::find(xpath,place_editable_fields,sizeof(place_editable_fields)/sizeof(place_editable_fields[0]));
						FCGI_printf("editable_field #%d\n",field);
						if (field>=0)
						{
							bool useOrigVal;
							char newVal[256];
							if (!place_editable_fields[field].validate_func((bufptr?bufptr:buf), &useOrigVal, newVal, sizeof(newVal)))
								throw BeerCrushException("Invalid value");
							else
							{
								if (useOrigVal)
									setValue(doc,(xmlChar*)xpath,(xmlChar*)(bufptr?bufptr:buf));
								else
									setValue(doc,(xmlChar*)xpath,(xmlChar*)newVal);
							}
						}
					
						if (bufptr) // If we had to alloc space
							free(bufptr);
					}
				}
				catch (...)
				{
					cgiStringArrayFree(fields);
					throw;
				}

				cgiStringArrayFree(fields);
			}

			xmlSaveFormatFile(place_filename.string().c_str(), doc, 1);
		
			xmlFreeDoc(doc);
		}
	}
	catch (exception& x)
	{
		// TODO: report failure
		FCGI_printf("Exception: %s\n",x.what());
		
	}
	
	return 0;
}
