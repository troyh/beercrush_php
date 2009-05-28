#include <oak.h>

class DataObj
{
public:	
};

class BeerReview
{
	map<std::string, > data;
public:	
};

OAK_RESULT validateBeerID(const char* name, const char* value, OAK_CGIFIELD_VALUE_TYPE converted_value)
{
	// TODO: verify that the ID makes sense, i.e., we have the brewery and the beer already
	return OAK_OK;
}


const char* OAK::OAK::conf_file="/etc/BeerCrush/BeerCrush.conf";
unsigned int OAK::OAK::cgi_flags=OAK_CGI_REQUIRE_USERID;
OAK_CGI_FIELD OAK::OAK::cgi_fields[]=
{
	OAK_CGI_FIELD("beer_id"			, OAK_CGIFIELD_REQUIRED,OAK_DATATYPE_TEXT,  0,-1, validateBeerID ),
	OAK_CGI_FIELD("rating"			, OAK_CGIFIELD_REQUIRED,OAK_DATATYPE_UINT16,  0, 5, NULL ),
	OAK_CGI_FIELD("srm"				, 0, 					OAK_DATATYPE_UINT16,  0, 9, NULL ),
	OAK_CGI_FIELD("body"			, 0, 					OAK_DATATYPE_UINT16,  0, 5, NULL ),
	OAK_CGI_FIELD("bitterness"		, 0, 					OAK_DATATYPE_UINT16,  0, 5, NULL ),
	OAK_CGI_FIELD("sweetness"		, 0, 					OAK_DATATYPE_UINT16,  0, 5, NULL ),
	OAK_CGI_FIELD("aftertaste"		, 0, 					OAK_DATATYPE_UINT16,  0, 5, NULL ),
	OAK_CGI_FIELD("comments"		, 0, 					OAK_DATATYPE_TEXT,  0,-1, NULL ),
	OAK_CGI_FIELD("price"			, 0, 					OAK_DATATYPE_MONEY, 0,-1, NULL ),
	OAK_CGI_FIELD("place"			, 0, 					OAK_DATATYPE_TEXT,  0,-1, NULL ),
	OAK_CGI_FIELD("size"			, 0, 					OAK_DATATYPE_TEXT,  0,-1, NULL ),
	OAK_CGI_FIELD("drankwithfood"	, 0, 					OAK_DATATYPE_TEXT,  0,-1, NULL ),
	OAK_CGI_FIELD("food_recommended", 0, 					OAK_DATATYPE_BOOL,  0,-1, NULL ),
	OAK_CGI_FIELD_TERMINATOR()
};

int oakInit()
{
}

int oakUninit()
{
}

bool oakException(OAK::Exception& x)
{
	return false; // Let OAK handle it
}


int oakMain(OAK::OAK& oak)
{
	cgiHeaderContentType((char*)"text/plain");
	FCGI_printf("oakMain()\n");
	// User is authorized, fields are validated, required fields exist
	
	char user_id[MAX_USERID_LEN];
	oak.get_user_id(user_id,sizeof(user_id));

	BeerReview review;

	// review.user_id=user_id;
	oak.assign_to_thriftobj(review.beer_id,"beer_id");
	oak.assign_to_thriftobj(review.rating,"rating");
	oak.assign_to_thriftobj(review.srm,"srm");
	oak.assign_to_thriftobj(review.body,"body");
	oak.assign_to_thriftobj(review.bitterness,"bitterness");
	oak.assign_to_thriftobj(review.sweetness,"sweetness");
	oak.assign_to_thriftobj(review.aftertaste,"aftertaste");
	oak.assign_to_thriftobj(review.comments,"comments");
	oak.assign_to_thriftobj(review.price,"price");
	oak.assign_to_thriftobj(review.place,"place");
	oak.assign_to_thriftobj(review.size,"size");
	oak.assign_to_thriftobj(review.drankwithfood,"drankwithfood");
	if (oak.field_exists("drankwithfood"))
		oak.assign_to_thriftobj(review.food_recommended,"food_recommended");

	FCGI_printf("storing object...\n");
	FCGI_printf("beer_id:%s\n",review.beer_id.c_str());
	FCGI_printf("rating:%d\n",review.rating);
	oak.store_object("BeerCrush","review/beer/",review);
	FCGI_printf("object stored\n");
	
	BeerReview review2;
	oak.get_object("BeerCrush","review/beer/",review2);
	FCGI_printf("object retrieved\n");
	
	FCGI_printf("beer_id:%s\n",review2.beer_id.c_str());
	FCGI_printf("rating:%d\n",review2.rating);
	
	// 
	// oak.add_field("user_id",user_id);
	// 
	// xmlDocPtr doc=NULL;
	// OAK_RESULT r=oak.xslt("beer/review_doc.xsl",&doc);
	// 
	// if (r!=OAK_OK)
	// 	FCGI_printf("XSLT failed:%s\n",oak.get_result_string(r));
	// else
	// {
	// 	xmlBufferPtr docbuf=oak.document_in_memory(doc);
	// 	FCGI_printf("Result:\n%s\n",xmlBufferContent(docbuf));
	// 	xmlBufferFree(docbuf);
	// 
	// 	// FCGI_printf("doc=%p\n",doc);
	// 	time_t now=time(0);
	// 	struct tm* dt=localtime(&now);
	// 	char docname[OAK_MAX_DOCNAME_LENGTH];
	// 	sprintf(docname,"review/beer/%s/%s-%04d%02d%02d%02d%02d%02d.xml",oak.get_field_value("beer_id"),user_id,dt->tm_year+1900,dt->tm_mon+1,dt->tm_mday,dt->tm_hour,dt->tm_min,dt->tm_sec);
	// 	
	// 	FCGI_printf("docname:%s\n",docname);
	// 	
	// 	oak.store_document(docname,doc);
	// }
	// 
	// if (doc)
	// 	xmlFreeDoc(doc);
	
	return 0;
}
