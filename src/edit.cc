#include <fcgi_stdio.h>
extern "C"
{
#include <cgic.h>
}

#include <stdlib.h>
#include <ctype.h>
#include <string.h>

// Boost stuff
#include <boost/filesystem.hpp>

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


bool validate_place_state(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
bool validate_place_price_range(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
bool validate_place_type(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
bool validate_price(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
bool validate_beer_upc(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
bool validate_beer_bjcp_style_id(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);


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

EDITABLE_FIELDS brewery_editable_fields[]=
{ // IMPORTANT: These must be sorted! They are searched with a binary search.
	{ "/brewery/address/city",				EDITABLE_FIELDS::validate_text },
	{ "/brewery/address/country",			EDITABLE_FIELDS::validate_text },
	{ "/brewery/address/latitude",			EDITABLE_FIELDS::validate_float },
	{ "/brewery/address/longitude",			EDITABLE_FIELDS::validate_float },
	{ "/brewery/address/state",				validate_place_state },
	{ "/brewery/address/street",			EDITABLE_FIELDS::validate_text },
	{ "/brewery/address/zip",				EDITABLE_FIELDS::validate_uinteger },
	{ "/brewery/name",						EDITABLE_FIELDS::validate_text },
	{ "/brewery/phone",						EDITABLE_FIELDS::validate_phone },
};

EDITABLE_FIELDS beer_editable_fields[]=
{ // IMPORTANT: These must be sorted! They are searched with a binary search.
	{ "/beer/@abv",										EDITABLE_FIELDS::validate_uinteger },
	{ "/beer/@brewery_id",								EDITABLE_FIELDS::validate_text },
	{ "/beer/@calories_per_ml",							EDITABLE_FIELDS::validate_float },
	{ "/beer/@ibu",										EDITABLE_FIELDS::validate_uinteger },
	{ "/beer/availability",								EDITABLE_FIELDS::validate_text },
	{ "/beer/description",								EDITABLE_FIELDS::validate_text },
	{ "/beer/grains",									EDITABLE_FIELDS::validate_text },
	{ "/beer/hops",										EDITABLE_FIELDS::validate_text },
	{ "/beer/ingredients",								EDITABLE_FIELDS::validate_text },
	{ "/beer/name",										EDITABLE_FIELDS::validate_text },
	{ "/beer/otherings",								EDITABLE_FIELDS::validate_text },
	{ "/beer/sizes/size/@upc",							validate_beer_upc },
	{ "/beer/sizes/size/description",					EDITABLE_FIELDS::validate_text },
	{ "/beer/sizes/size/distributor/deposit",			validate_price },
	{ "/beer/sizes/size/distributor/item",				EDITABLE_FIELDS::validate_text },
	{ "/beer/sizes/size/distributor/name",				EDITABLE_FIELDS::validate_text },
	{ "/beer/sizes/size/distributor/net_case_price",	validate_price },
	{ "/beer/sizes/size/distributor/post_off",			EDITABLE_FIELDS::validate_text },
	{ "/beer/sizes/size/distributor/reg_price",			validate_price },
	{ "/beer/sizes/size/distributor/unit_price",		validate_price },
	{ "/beer/styles/@bjcp_style_id",					validate_beer_bjcp_style_id },
	{ "/beer/yeast",									EDITABLE_FIELDS::validate_text },
};

EDITABLE_DOCTYPES doctypes[]=
{ // IMPORTANT: These must be sorted! They are searched with a binary search.
	{ "/beer"   , "beer_id"   , "beer"   , beer_editable_fields   , sizeof(beer_editable_fields)/sizeof(beer_editable_fields[0])       },
	{ "/brewery", "brewery_id", "brewery", brewery_editable_fields, sizeof(brewery_editable_fields)/sizeof(brewery_editable_fields[0]) },
	{ "/place"  , "place_id"  , "place"  , place_editable_fields  , sizeof(place_editable_fields)/sizeof(place_editable_fields[0])     },
};

int cgiMain()
{
	if (!userIsValidated())
	{
		cgiHeaderStatus(401,(char*)"User could not be validated");
		return 0;
	}
	
	try
	{
		bfs::path xml_doc_dir("/home/troy/beerliberation/xml");

		cgiHeaderContentType((char*)"text/plain");
		
		char userid[MAX_USERID_LEN];
		cgiCookieString((char*)"userid",userid,sizeof(userid));

		// FCGI_printf("userid:%s\n",userid);
		// FCGI_printf("Path:%s\n",cgiPathInfo);
		
		int doctype_num=EDITABLE_DOCTYPES::find(cgiPathInfo, doctypes, sizeof(doctypes)/sizeof(doctypes[0]));
		if (doctype_num<0)
			throw BeerCrushException("Invalid document type");
			
		char identifier[BEERCRUSH_MAX_PLACE_ID_LEN];
		if (cgiFormString((char*)doctypes[doctype_num].id_field,identifier,sizeof(identifier))!=cgiFormSuccess)
			throw BeerCrushException("Unable to get identifier");

		bfs::path xml_filename=xml_doc_dir / doctypes[doctype_num].xmldirpath / identifier;
		xml_filename=bfs::change_extension(xml_filename,".xml");
		
		// FCGI_printf("XML doc:%s\n",place_filename.string().c_str());
		// TODO: check if file exists

		editDoc(xml_filename,doctypes[doctype_num].editable_fields,doctypes[doctype_num].editable_fields_count,doctypes[doctype_num].id_field,cgiPathInfo);

	}
	catch (exception& x)
	{
		// TODO: report failure as XML
		FCGI_printf("Exception: %s\n",x.what());
	}
	
	return 0;
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
	int lo=0,hi=(sizeof(acceptables)/sizeof(acceptables[0]))-1,mid;
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

bool validate_price(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	// It's ok if all chars are digits or decimal point or '$'
	for(size_t i = 0,len=strlen(s); i < len; ++i)
	{
		if (!isdigit(s[i]) && s[i]!='.' && s[i]!='$')
			return false;
	}
	*useOrigVal=true;
	return true;
}

bool validate_beer_upc(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	// It's ok if all chars are digits or spaces or hyphens
	for(size_t i = 0,len=strlen(s); i < len; ++i)
	{
		if (!isdigit(s[i]) && !isspace(s[i]) && s[i]!='-')
			return false;
	}
	*useOrigVal=true;
	return true;
}

bool validate_beer_bjcp_style_id(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	// It's ok if all chars are digits followed by an optional letter
	for(size_t i = 0,len=strlen(s); i < len; ++i)
	{
		if (!isalnum(s[i]))
			return false;
	}
	// And the value must be between 1 and 23 (inclusive)
	int n=atoi(s);
	if (n<1 || n>23)
		return false;
		
	*useOrigVal=true;
	return true;
}
