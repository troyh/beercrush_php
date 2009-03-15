#include <string.h>

#include "beercrush.h"

int EDITABLE_FIELDS::find(const char* xpath, EDITABLE_FIELDS* fields, size_t fields_count)
{
	int lo=0,hi=fields_count,mid;
	
	while (lo<hi)
	{
		mid=(lo+hi)/2;
		int c=strcmp(xpath,fields[mid].xpath);
		if (c==0)
			return mid;
		if (c>0)
			lo=mid+1;
		else
			hi=mid-1;
	}
	
	return -1;
}


bool EDITABLE_FIELDS::validate_yesno(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	if (newValSize<4)
		return false;

	if (!strcasecmp(s,"yes"))
	{
		*useOrigVal=true;
		return true;
	}
	else if (!strcasecmp(s,"no"))
	{
		*useOrigVal=true;
		return true;
	}
	else if (!strcasecmp(s,"y"))
	{
		*useOrigVal=false;
		strncpy(newVal,"yes",sizeof(newValSize));
		return true;
	}
	else if (!strcasecmp(s,"n"))
	{
		*useOrigVal=false;
		strncpy(newVal,"no",sizeof(newValSize));
		return true;
	}
	
	return false;
}

bool EDITABLE_FIELDS::validate_uinteger(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	// It's ok if all chars are digits
	for(size_t i = 0,len=strlen(s); i < len; ++i)
	{
		if (!isdigit(s[i]))
			return false;
	}
	*useOrigVal=true;
	return true;
}

bool EDITABLE_FIELDS::validate_text(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	*useOrigVal=true;
	return true;
}

bool EDITABLE_FIELDS::validate_phone(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	// It's ok if all chars are digits or spaces or parens
	for(size_t i = 0,len=strlen(s); i < len; ++i)
	{
		if (!isdigit(s[i]) && !isspace(s[i]) && s[i]!='(' && s[i]!=')')
			return false;
	}
	*useOrigVal=true;
	return true;
}

bool EDITABLE_FIELDS::validate_uri(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	*useOrigVal=true;
	return true;
}

bool EDITABLE_FIELDS::validate_float(const char* s, bool* useOrigVal, char* newVal, size_t newValSize)
{
	// It's ok if all chars are digits or decimals or +/-
	for(size_t i = 0,len=strlen(s); i < len; ++i)
	{
		if (!isdigit(s[i]) && s[i]!='.' && s[i]!='+' && s[i]!='-')
			return false;
	}
	*useOrigVal=true;
	return true;
}

