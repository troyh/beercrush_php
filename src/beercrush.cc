extern "C"
{
#include <cgic.h>
}

#include <string.h>

// libxml2 stuff
#include <libxml/xmlmemory.h>
#include <libxml/parser.h>
#include <libxml/xmlwriter.h>
#include <libxml/xpath.h>


#include "beercrush.h"


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
	// FCGI_printf("setValue(%p,%s,%s)\n",doc,xpath,value);
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

void editDoc(boost::filesystem::path xml_file,EDITABLE_FIELDS* editable_fields, size_t editable_fields_count, const char* id_string, const char* xpath_prefix)
{
	xmlDocPtr doc;
	doc=xmlParseFile(xml_file.string().c_str());
	if (!doc)
		throw BeerCrushException("Unable to open document");

	char content_type[256];
	cgiFormResultType res=cgiFormFileContentType((char*)"file",content_type,sizeof(content_type));
	if (res!=cgiFormNoContentType && res!=cgiFormNotFound)
		throw BeerCrushException("Unsupported content type");
		
	char** cgi_fields;
	if (cgiFormEntries(&cgi_fields)==cgiFormSuccess)
	{
		try
		{
			// Count the number of fields and bytes needed for the data
			for(size_t i = 0; cgi_fields[i]; ++i)
			{
				// Ignore id_string field, it's just used to identify the document, not any element in the doc
				if (!strcmp(cgi_fields[i],id_string))
					continue;
					
				char buf[256];
				char* bufptr=0;
		
				int n;
				cgiFormStringSpaceNeeded(cgi_fields[i],&n);
				if (n>sizeof(buf))
				{	// Alloc space for it
					bufptr=(char*)calloc(1,n);
					if (!bufptr)
						throw BeerCrushException("Internal error");
				}

				res=cgiFormString(cgi_fields[i],(bufptr?bufptr:buf),(bufptr?n:sizeof(buf)));
				if (res!=cgiFormSuccess && res!=cgiFormEmpty) // empty values are okay too
					throw BeerCrushException("CGI error");

				// cgi_fields[i] is an xpath string
				char xpath[256];
				strncpy(xpath,xpath_prefix,sizeof(xpath)-1);
				xpath[sizeof(xpath)-1]='\0';
				strncat(xpath,"/",sizeof(xpath)-strlen(xpath)-1);
				xpath[sizeof(xpath)-1]='\0';
				strncat(xpath,cgi_fields[i],sizeof(xpath)-strlen(xpath)-1);
				xpath[sizeof(xpath)-1]='\0';
				
				// FCGI_printf("finding %s\n",xpath);
				int field=EDITABLE_FIELDS::find(xpath,editable_fields,editable_fields_count);
				// FCGI_printf("editable_field #%d\n",field);
				if (field>=0)
				{
					bool useOrigVal;
					char newVal[256];
					if (!editable_fields[field].validate_func((bufptr?bufptr:buf), &useOrigVal, newVal, sizeof(newVal)))
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
			cgiStringArrayFree(cgi_fields);
			throw;
		}

		cgiStringArrayFree(cgi_fields);
	}

	xmlSaveFormatFile(xml_file.string().c_str(), doc, 1);

	xmlFreeDoc(doc);
	
}


int EDITABLE_FIELDS::find(const char* xpath, EDITABLE_FIELDS* fields, size_t fields_count)
{
	int lo=0,hi=fields_count-1,mid=0;
	
	while (lo<=hi)
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
	// It's ok if all chars are digits or spaces or parens or hyphens
	for(size_t i = 0,len=strlen(s); i < len; ++i)
	{
		if (!isdigit(s[i]) && !isspace(s[i]) && s[i]!='(' && s[i]!=')' && s[i]!='-')
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



int EDITABLE_DOCTYPES::find(const char* pathinfo, EDITABLE_DOCTYPES* types, size_t types_count)
{
	int lo=0,hi=types_count-1,mid=0;
	
	while (lo<=hi)
	{
		mid=(lo+hi)/2;
		int c=strcmp(pathinfo,types[mid].pathinfo);
		if (c==0)
			return mid;
		if (c>0)
			lo=mid+1;
		else
			hi=mid-1;
	}
	
	return -1;
}
