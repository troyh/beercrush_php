#define NO_FCGI_DEFINES
#include <fcgi_stdio.h>

extern "C"
{
#include <cgic.h>
}

#include <oak.h>
#include <string>
#include <string.h>

#include <boost/filesystem.hpp>

#include <libxml/xmlwriter.h>
#include <libxml/xmlsave.h>

using namespace std;
namespace bfs=boost::filesystem;

OAK::OAK oak("/etc/BeerCrush/BeerCrush.conf");

extern "C"
void cgiInit()
{
}

extern "C"
void cgiUninit()
{
}

OAK_RESULT validateBeerID(const char* name, const char* value)
{
	// TODO: verify that the ID makes sense, i.e., we have the brewery and the beer already
	return OAK_OK;
}

OAK_RESULT validateRange(const char* name, const char* value)
{
	int n=atoi(value);
	if (!strcmp(name,"srm"))
	{
		if (n<0 || n>9)
			return OAK_VALIDATE_BADVALUE;
	}
	else if (!strcmp(name,"rating") || 
			!strcmp(name,"body")	||
			!strcmp(name,"bitterness") ||
			!strcmp(name,"sweetness") ||
			!strcmp(name,"aftertaste"))
	{
		if (n<0 || n>5)
			return OAK_VALIDATE_BADVALUE;
	}
	return OAK_OK;
}


int cgiMain()
{
	try
	{
		// Authorize the user
		if (!oak.auth_user())
		{
			// Not authorized
			cgiHeaderContentType((char*)"text/plain");
			FCGI_printf("Not authorized");
		}
		else
		{
			char user_id[MAX_USERID_LEN]="troyh";
			if (false&&oak.get_user_id(user_id,sizeof(user_id))!=OAK_OK)
			{
				cgiHeaderContentType((char*)"text/plain");
				FCGI_printf("Missing userid");
			}
			else
			{
				// Authorized, examine review for validity

				oak.validate_field("beer_id"			, OAK_DATATYPE_TEXT,  validateBeerID);
				oak.validate_field("rating"				, OAK_DATATYPE_UINT,  validateRange);
				oak.validate_field("srm"				, OAK_DATATYPE_UINT,  validateRange);
				oak.validate_field("body"				, OAK_DATATYPE_UINT,  validateRange);
				oak.validate_field("bitterness"			, OAK_DATATYPE_UINT,  validateRange);
				oak.validate_field("sweetness"			, OAK_DATATYPE_UINT,  validateRange);
				oak.validate_field("aftertaste"			, OAK_DATATYPE_UINT,  validateRange);
				oak.validate_field("comments"			, OAK_DATATYPE_TEXT);
				oak.validate_field("price"				, OAK_DATATYPE_MONEY);
				oak.validate_field("place"				, OAK_DATATYPE_TEXT);
				oak.validate_field("size"				, OAK_DATATYPE_TEXT);
				oak.validate_field("drankwithfood"		, OAK_DATATYPE_TEXT);
				oak.validate_field("food_recommended"	, OAK_DATATYPE_TEXT);
			
				if (!oak.get_field_value("beer_id") || !oak.get_field_value("rating"))
				{
					// These are all that are required
					cgiHeaderContentType((char*)"text/plain");
					FCGI_printf("beer_id and rating are required.\n");
				}
				else if (oak.invalid_fields_count())
				{
					cgiHeaderContentType((char*)"text/plain");
					FCGI_printf("# invalid fields:%d\n",oak.invalid_fields_count());
					for (size_t i=0,n=oak.invalid_fields_count(); i<n; ++i)
						FCGI_printf("%d: %s=%s\n",i+1,oak.get_invalid_field_name(i),oak.get_invalid_field_value(i));
				}
				else
				{
					cgiHeaderContentType((char*)"text/plain");
					
					oak.add_field("user_id",user_id);

					xmlDocPtr doc=NULL;
					OAK_RESULT r=oak.xslt("beer/review_doc.xsl",&doc);

					if (r!=OAK_OK)
						FCGI_printf("XSLT failed:%s\n",oak.get_result_string(r));
					else
					{
						xmlBufferPtr docbuf=oak.document_in_memory(doc);
						FCGI_printf("Result:\n%s\n",xmlBufferContent(docbuf));
						xmlBufferFree(docbuf);
					
						// FCGI_printf("doc=%p\n",doc);
						// time_t now=time(0);
						// struct tm* dt=localtime(&now);
						// char docname[OAK_MAX_DOCNAME_LENGTH];
						// sprintf(docname,"review/beer/%s/%s-%04d%02d%02d%02d%02d%02d.xml",oak.get_field_value("beer_id"),user_id,dt->tm_year+1900,dt->tm_mon+1,dt->tm_mday,dt->tm_hour,dt->tm_min,dt->tm_sec);
						// 
						// FCGI_printf("docname:%s\n",docname);
						// 
						// oak.store_document(docname,doc);
					}
					
					if (doc)
						xmlFreeDoc(doc);
				}
			}
		}
	}
	catch (OAK::Exception& x)
	{
		FCGI_printf("OAK Exception:%s",x.what());
	}
	catch (...)
	{
		FCGI_printf("Unknown exception\n");
	}
	
	return 0;
}
