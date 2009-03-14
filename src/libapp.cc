#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

#include <boost/filesystem.hpp>

#include <libxml/xmlmemory.h>
#include <libxml/parser.h>
#include <libxml/xmlwriter.h>
#include <libxml/xpath.h>

#include <cgic.h>

#include <OAK/oak.h>

namespace bfs=boost::filesystem;


xmlXPathObjectPtr queryXPath(xmlDocPtr doc, const xmlChar* xpath)
{
	xmlXPathContextPtr context; 
	xmlXPathObjectPtr result; 
	context = xmlXPathNewContext(doc); 
	if (context == NULL) 
	{ 
		return NULL; 
	} 
	result = xmlXPathEvalExpression(xpath, context); 
	xmlXPathFreeContext(context); 
	if (result == NULL) 
	{ 
		return NULL; 
	} 
	if(xmlXPathNodeSetIsEmpty(result->nodesetval))
	{ 
		xmlXPathFreeObject(result); 
		return NULL; 
	} 
	return result; 
	
}

extern "C"
int createlogin_success(const char* userid, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	return 0;
	// int bFailed=true; // Assume it failed
	// 
	// const char* email=postdata.get("email");
	// const char* password=postdata.get("password");
	// 
	// if (userid   && strlen(userid) &&
	// 	email    && strlen(email)  &&
	// 	password && strlen(password))
	// {
	// 	bfs::path user_filename("/home/troy/beerliberation/xml/user/");
	// 	user_filename=user_filename / userid;
	// 	user_filename=bfs::change_extension(user_filename,".xml");
	// 
	// 	struct stat sb;
	// 	if (stat(user_filename.string().c_str(),&sb))
	// 	{
	// 		// It's new, store the new user doc
	// 	
	// 		xmlTextWriterPtr w=xmlNewTextWriterFilename(user_filename.string().c_str(),0);
	// 		if (w)
	// 		{
	// 			if (xmlTextWriterStartDocument(w,NULL,"UTF-8",NULL)<0)
	// 			{
	// 			}
	// 			else if (xmlTextWriterStartElement(w,(const xmlChar*)"user")<0)
	// 			{
	// 			}
	// 			else if (xmlTextWriterWriteAttribute(w,(const xmlChar*)"id", (const xmlChar*)userid))
	// 			{
	// 			}
	// 			else if (xmlTextWriterWriteElement(w,(const xmlChar*)"ipaddr", (const xmlChar*)(cgiData->cgiRemoteAddr)))
	// 			{
	// 			}
	// 			else if (xmlTextWriterWriteElement(w,(const xmlChar*)"email", (const xmlChar*)cgiData->email))
	// 			{
	// 			}
	// 			else if (xmlTextWriterWriteElement(w,(const xmlChar*)"password", (const xmlChar*)password))
	// 			{
	// 			}
	// 			else if (xmlTextWriterEndElement(w))
	// 			{
	// 			}
	// 			else if (xmlTextWriterEndDocument(w))
	// 			{
	// 			}
	// 			else
	// 			{
	// 				// Success!
	// 				bFailed=false;
	// 			}
	// 		
	// 			xmlFreeTextWriter(w);
	// 		}
	// 	}
	// 	else
	// 	{
	// 		// Account already exists, don't overwrite
	// 	}
	// }
	// 
	// return bFailed;
}

extern "C"
int createlogin_failed(const char* userid, CREATELOGIN_FAILED_REASON reason, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	return 0;
}

extern "C"
int login_success(const char* userid, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	NAMEVAL_PAIR* nvp=(NAMEVAL_PAIR*)calloc(1,sizeof(NAMEVAL_PAIR));
	*nv_pairs_count=1;
	nvp->name=strdup("func");
	nvp->val=strdup("login_success");
	*nv_pairs=nvp;
	return 0;
}

extern "C"
int login_failed(const char* userid, LOGIN_FAILED_REASON reason, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	NAMEVAL_PAIR* nvp=(NAMEVAL_PAIR*)calloc(1,sizeof(NAMEVAL_PAIR));
	*nv_pairs_count=1;
	nvp->name=strdup("func");
	nvp->val=strdup("login_failed");
	*nv_pairs=nvp;
	return 0;
}

extern "C"
int logout_success(const char* userid, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	NAMEVAL_PAIR* nvp=(NAMEVAL_PAIR*)calloc(1,sizeof(NAMEVAL_PAIR));
	*nv_pairs_count=1;
	nvp->name=strdup("func");
	nvp->val=strdup("logout_success");
	*nv_pairs=nvp;
	return 0;
}

extern "C"
int logout_failed(const char* userid, LOGIN_FAILED_REASON reason, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	NAMEVAL_PAIR* nvp=(NAMEVAL_PAIR*)calloc(1,sizeof(NAMEVAL_PAIR));
	*nv_pairs_count=1;
	nvp->name=strdup("func");
	nvp->val=strdup("logout_failed");
	*nv_pairs=nvp;
	return 0;
}

extern "C"
int post_success(const char* doctype,const char* userid, NAMEVAL_PAIR* post_data, size_t post_data_len, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	NAMEVAL_PAIR* nvp=(NAMEVAL_PAIR*)calloc(post_data_len,sizeof(NAMEVAL_PAIR));
	if (nvp)
	{
		for(size_t i = 0; i < post_data_len; ++i)
		{
			nvp[i].name=strdup(post_data[i].name);
			nvp[i].val=strdup(post_data[i].val);
		}
		
		*nv_pairs=nvp;
		*nv_pairs_count=post_data_len;
	}
	
	NAMEVAL_PAIR_OBJ postdata(post_data,post_data_len);
	
	if (!strcmp(doctype,"beer_review"))
	{
		// Check to see if the review already exists
		bfs::path review_filename("/home/troy/beerliberation/xml/review/beer/");
		review_filename=review_filename / postdata.get("beer_id") / userid;
		review_filename=bfs::change_extension(review_filename,".xml");
	
		struct stat sb;
		if (stat(review_filename.string().c_str(),&sb))
		{
			// It's new, store the new review doc
			// TODO: Do not allow a directory for a brewery that doesn't exist to be created
			bfs::create_directories(review_filename.parent_path());
			
			xmlTextWriterPtr w=xmlNewTextWriterFilename(review_filename.string().c_str(),0);
			if (w)
			{
				if (xmlTextWriterStartDocument(w,NULL,"UTF-8",NULL)<0)
				{
				}
				else if (xmlTextWriterStartElement(w,(const xmlChar*)"review")<0)
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"user_id", (const xmlChar*)userid))
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"ipaddr", (const xmlChar*)(cgiRemoteAddr?cgiRemoteAddr:"")))
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"beer_id", (const xmlChar*)postdata.get("beer_id")))
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"rating", (const xmlChar*)postdata.get("rating")))
				{
				}
				else if (xmlTextWriterEndElement(w))
				{
				}
				else if (xmlTextWriterEndDocument(w))
				{
				}
				
				xmlFreeTextWriter(w);
			}
		}
		else
		{
			// It already exists, update the existing doc with the new review
			xmlDocPtr doc;
			doc=xmlParseFile(review_filename.string().c_str());
			if (!doc)
			{
				
			}
			else
			{
				xmlXPathObjectPtr nodeset=queryXPath(doc,(const xmlChar*)"/review/rating");
				if (!nodeset)
				{
					// Add the element
					xmlNodePtr root=xmlDocGetRootElement(doc);
					xmlNewTextChild(root,NULL,(const xmlChar*)"review",(const xmlChar*)postdata.get("rating"));
				}
				else
				{
					// Update the element
					xmlNodeSetContent(nodeset->nodesetval->nodeTab[0],(const xmlChar*)postdata.get("rating"));
					xmlXPathFreeObject(nodeset);
				}
				
				xmlSaveFormatFile(review_filename.string().c_str(), doc, 1);
				
				xmlFreeDoc(doc);
			}
		}
	}
	else if (!strcmp(doctype,"place_review"))
	{
		// Check to see if the review already exists
		bfs::path review_filename("/home/troy/beerliberation/xml/review/place/");
		review_filename=review_filename / postdata.get("place_id") / userid;
		review_filename=bfs::change_extension(review_filename,".xml");
	
		struct stat sb;
		if (stat(review_filename.string().c_str(),&sb))
		{
			// It's new, store the new review doc
			// TODO: Do not allow a directory for a place that doesn't exist to be created
			bfs::create_directories(review_filename.parent_path());
			
			xmlTextWriterPtr w=xmlNewTextWriterFilename(review_filename.string().c_str(),0);
			if (w)
			{
				if (xmlTextWriterStartDocument(w,NULL,"UTF-8",NULL)<0)
				{
				}
				else if (xmlTextWriterStartElement(w,(const xmlChar*)"review")<0)
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"user_id", (const xmlChar*)userid))
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"ipaddr", (const xmlChar*)(cgiRemoteAddr?cgiRemoteAddr:"")))
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"place_id", (const xmlChar*)postdata.get("beer_id")))
				{
				}
				else if (xmlTextWriterWriteElement(w,(const xmlChar*)"rating", (const xmlChar*)postdata.get("rating")))
				{
				}
				else if (xmlTextWriterEndElement(w))
				{
				}
				else if (xmlTextWriterEndDocument(w))
				{
				}
				
				xmlFreeTextWriter(w);
			}
		}
		else
		{
			// It already exists, update the existing doc with the new review
			xmlDocPtr doc;
			doc=xmlParseFile(review_filename.string().c_str());
			if (!doc)
			{
				
			}
			else
			{
				xmlXPathObjectPtr nodeset=queryXPath(doc,(const xmlChar*)"/review/rating");
				if (!nodeset)
				{
					// Add the element
					xmlNodePtr root=xmlDocGetRootElement(doc);
					xmlNewTextChild(root,NULL,(const xmlChar*)"review",(const xmlChar*)postdata.get("rating"));
				}
				else
				{
					// Update the element
					xmlNodeSetContent(nodeset->nodesetval->nodeTab[0],(const xmlChar*)postdata.get("rating"));
					xmlXPathFreeObject(nodeset);
				}
				
				xmlSaveFormatFile(review_filename.string().c_str(), doc, 1);
				
				xmlFreeDoc(doc);
			}
		}
	}
	
	return 0;
}

extern "C"
int post_failed(const char* doctype,const char* userid,POST_FAILED_REASON reason, NAMEVAL_PAIR** nv_pairs, size_t* nv_pairs_count)
{
	NAMEVAL_PAIR* nvp=(NAMEVAL_PAIR*)calloc(1,sizeof(NAMEVAL_PAIR));
	*nv_pairs_count=1;
	nvp->name=strdup("func");
	nvp->val=strdup("post_failed");
	*nv_pairs=nvp;
	return 0;
}
