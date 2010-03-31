#include <fcgiapp.h>
#include <stdlib.h>
#include <string.h>
#include <jansson.h>
#include <curl/curl.h>
#include <openssl/md5.h>

extern "C"
{
#include "../external/cgic/cgic.h"
}

char couchdb_userdoc_url[1024]="";


size_t curl_response( void *ptr, size_t size, size_t nmemb, void *stream)
{
	char** mybuffer=(char**)stream;
	if (*mybuffer==NULL)
	{ // Alloc some memory for it
		*mybuffer=(char*)calloc(nmemb,size);
	}
	
	if (*mybuffer)
	{
		memcpy(*mybuffer,ptr,size*nmemb);
		(*mybuffer)[size*nmemb]='\0'; // null-terminate it
	}
	
	return size*nmemb;
}

bool login_is_trusted(/*FCGX_Stream* out*/)
{
	bool isTrusted=false;
	
	// if ($this->get_document('user:'.$this->get_user_id(),$user_doc)!==true)
	// 	return false;
	// 
	// $correct_key=md5($this->get_user_id().$user_doc->secret.$_SERVER['REMOTE_ADDR']);
	// if ($correct_key!==$this->get_user_key())
	// 	return false;
	
	// Copy the base URL (that never changes)
	char url[sizeof(couchdb_userdoc_url)];
	strncpy(url,couchdb_userdoc_url,sizeof(url));
	url[sizeof(url)-1]='\0';
	size_t url_len=strlen(url);
	
	// Add the user_id
	cgiFormString("userid",&url[url_len],sizeof(url)-url_len);
	url[sizeof(url)-1]='\0';
	
	// Get user document from couchdb
	CURL* ch=curl_easy_init();
	if (ch)
	{
		char* userdoc=0;
		
		curl_easy_setopt(ch,CURLOPT_URL,url);
		curl_easy_setopt(ch,CURLOPT_WRITEFUNCTION,curl_response);
		curl_easy_setopt(ch,CURLOPT_WRITEDATA,&userdoc);
		
		if (curl_easy_perform(ch)==0)
		{
			// FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
			// FCGX_FPrintF(out,"URL: %s\n",url);
			// double t;
			// curl_easy_getinfo(ch,CURLINFO_TOTAL_TIME,&t);
			// FCGX_FPrintF(out,"CURLINFO_TOTAL_TIME=%f\n",t);
			// curl_easy_getinfo(ch,CURLINFO_NAMELOOKUP_TIME,&t);
			// FCGX_FPrintF(out,"CURLINFO_NAMELOOKUP_TIME=%f\n",t);
			// curl_easy_getinfo(ch,CURLINFO_CONNECT_TIME,&t);
			// FCGX_FPrintF(out,"CURLINFO_CONNECT_TIME=%f\n",t);
			// curl_easy_getinfo(ch,CURLINFO_APPCONNECT_TIME,&t);
			// FCGX_FPrintF(out,"CURLINFO_APPCONNECT_TIME=%f\n",t);
			// curl_easy_getinfo(ch,CURLINFO_PRETRANSFER_TIME,&t);
			// FCGX_FPrintF(out,"CURLINFO_PRETRANSFER_TIME=%f\n",t);
			// curl_easy_getinfo(ch,CURLINFO_STARTTRANSFER_TIME,&t);
			// FCGX_FPrintF(out,"CURLINFO_STARTTRANSFER_TIME=%f\n",t);
			
			
			long status_code;
			curl_easy_getinfo(ch,CURLINFO_RESPONSE_CODE,&status_code);
			if (status_code==200)
			{
				// userdoc is a JSON doc, use Jansson to parse it...
				json_error_t error;
				json_t* jsondoc=json_loads(userdoc,&error);
				
				free(userdoc);
				
				// ...and then get the secret out of it
				if (jsondoc)
				{
					json_t* secret=json_object_get(jsondoc,"secret");
					if (json_is_integer(secret))
					{
						// Now get the MD5 sum of the user_id+secret+REMOTE_ADDR
						char str[256];
						snprintf(str,sizeof(str),"%s%d%s",&url[url_len],json_integer_value(secret),cgiRemoteAddr);
						unsigned char md5sum[MD5_DIGEST_LENGTH];
						MD5((unsigned char*)str,strlen(str),md5sum);
						
						// Convert MD5 to a 32-byte string
						char md5sum_str[MD5_DIGEST_LENGTH * 2 + 1];
						char hexchars[]="0123456789abcdef";
						for (size_t i=0; i < MD5_DIGEST_LENGTH; ++i)
						{
							md5sum_str[i*2]=hexchars[(md5sum[i] & 0xf0) >> 4];
							md5sum_str[i*2+1]=hexchars[md5sum[i] & 0x0f];
						}
						md5sum_str[sizeof(md5sum_str)-1]='\0';
		
						// Compare it to the CGI usr_key
						char usrkey[MD5_DIGEST_LENGTH * 2 + 1];
						cgiFormString("usrkey",usrkey,sizeof(usrkey));

						if (!strcmp(usrkey,md5sum_str))
						{
							isTrusted=true;
						}
					}
					
					json_decref(jsondoc);
				}
			}
		}
		
		curl_easy_cleanup(ch);
	}
	
	return isTrusted;
}

extern "C" void fcgiInit() 
{
	curl_global_init(CURL_GLOBAL_ALL);
	
	srandom(time(0)); // Seed the random number generator for the use of random() below
	
	// Get the IP address of the couchdb server
	char couchdb_server[32]="";
	char couchdb_dbname[32]="";
	
	json_error_t error;
	json_t* cfg=json_load_file("/etc/BeerCrush/webapp.conf",&error);
	if (cfg)
	{
		json_t* couchdb=json_object_get(cfg,"couchdb");
		if (json_is_object(couchdb))
		{
			json_t* nodes=json_object_get(couchdb,"nodes");
			if (json_is_array(nodes))
			{
				// Just pick one at random
				json_t* node=json_array_get(nodes,random() % json_array_size(nodes));
				if (json_is_string(node))
				{
					strncpy(couchdb_server,json_string_value(node),sizeof(couchdb_server));
					couchdb_server[sizeof(couchdb_server)-1]='\0'; // null-terminate it
					
					// Get the db name
					json_t* dbname=json_object_get(couchdb,"database");
					if (json_is_string(dbname))
					{
						strncpy(couchdb_dbname,json_string_value(dbname),sizeof(couchdb_dbname));
						couchdb_dbname[sizeof(couchdb_dbname)-1]='\0'; // null-terminate it
						
						snprintf(couchdb_userdoc_url,sizeof(couchdb_userdoc_url),"http://%s/%s/user:",couchdb_server,couchdb_dbname);
					}
				}
			}
		}
		json_decref(cfg);
	}
}

extern "C" void fcgiUninit() 
{
}

extern "C" int fcgiMain(FCGX_Stream *in,FCGX_Stream *out,FCGX_Stream *err,FCGX_ParamArray envp)
{
	// FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
	// 
	// FCGX_FPrintF(out,"cgiServerSoftware=%s\n",cgiServerSoftware);
	// FCGX_FPrintF(out,"cgiServerName=%s\n",cgiServerName);
	// FCGX_FPrintF(out,"cgiGatewayInterface=%s\n",cgiGatewayInterface);
	// FCGX_FPrintF(out,"cgiServerProtocol=%s\n",cgiServerProtocol);
	// FCGX_FPrintF(out,"cgiServerPort=%s\n",cgiServerPort);
	// FCGX_FPrintF(out,"cgiRequestMethod=%s\n",cgiRequestMethod);
	// FCGX_FPrintF(out,"cgiPathInfo=%s\n",cgiPathInfo);
	// FCGX_FPrintF(out,"cgiPathTranslated=%s\n",cgiPathTranslated);
	// FCGX_FPrintF(out,"cgiScriptName=%s\n",cgiScriptName);
	// FCGX_FPrintF(out,"cgiQueryString=%s\n",cgiQueryString);
	// FCGX_FPrintF(out,"cgiRemoteHost=%s\n",cgiRemoteHost);
	// FCGX_FPrintF(out,"cgiRemoteAddr=%s\n",cgiRemoteAddr);
	// FCGX_FPrintF(out,"cgiAuthType=%s\n",cgiAuthType);
	// FCGX_FPrintF(out,"cgiRemoteUser=%s\n",cgiRemoteUser);
	// FCGX_FPrintF(out,"cgiRemoteIdent=%s\n",cgiRemoteIdent);
	// FCGX_FPrintF(out,"cgiCookie=%s\n",cgiCookie);
	// FCGX_FPrintF(out,"cgiAccept=%s\n",cgiAccept);
	// FCGX_FPrintF(out,"cgiUserAgent=%s\n",cgiUserAgent);
	// FCGX_FPrintF(out,"cgiReferrer=%s\n",cgiReferrer);

	const int CGIPATH_WISHLIST_LEN=14;
	
	if (!strncmp(cgiPathInfo,"/api/beer/",10) ||
		!strcmp(cgiPathInfo,"/api/beercolors") ||
		!strcmp(cgiPathInfo,"/api/beers") ||
		!strcmp(cgiPathInfo,"/api/beerstyles") ||
		!strcmp(cgiPathInfo,"/api/breweries") ||
		!strncmp(cgiPathInfo,"/api/brewery/",13) ||
		!strncmp(cgiPathInfo,"/api/createlogin",16) ||
		!strcmp(cgiPathInfo,"/api/flavors") ||
		!strncmp(cgiPathInfo,"/api/history/",13) ||
		!strcmp(cgiPathInfo,"/api/login") ||
		!strcmp(cgiPathInfo,"/api/logout") ||
		!strcmp(cgiPathInfo,"/api/menu/edit") ||
		!strncmp(cgiPathInfo,"/api/menu/",10) ||
		!strncmp(cgiPathInfo,"/api/photoset/",14) ||
		!strncmp(cgiPathInfo,"/api/place/",11) ||
		!strcmp(cgiPathInfo,"/api/places") ||
		!strcmp(cgiPathInfo,"/api/restaurantcategories") ||
		!strncmp(cgiPathInfo,"/api/review/beer",16) ||
		!strncmp(cgiPathInfo,"/api/review/place",17) ||
		!strncmp(cgiPathInfo,"/api/image/",11) ||
		!strncmp(cgiPathInfo,"/api/user/",10) ||
		!strncmp(cgiPathInfo,"/api/users",10) ||
		!strcmp(cgiPathInfo,"/api/search"))
	{
		FCGX_FPrintF(out,"X-Accel-Redirect: /store%s%s%s\r\n",cgiPathInfo,(!strlen(cgiQueryString)?"":"?"),cgiQueryString);
		FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
	}
	else if (!strncmp(cgiPathInfo,"/api/wishlist/",CGIPATH_WISHLIST_LEN))
	{
		if (login_is_trusted(/*out*/)!=true)
		{
			// cgiHeaderStatus(403,"Login required");
			// FCGX_FPrintF(out,"HTTP/1.0 403 Permission denied\r\n");
			FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
			FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
			FCGX_FPrintF(out,"Login required\n");
		}
		else
		{
			// Verify that the requesting user has access permissions to this wishlist (i.e., it's their own wishlist)
			char user_id[256];
			cgiFormString("userid",user_id,sizeof(user_id));
			
			if (strcmp(user_id,cgiPathInfo+CGIPATH_WISHLIST_LEN))
			{
				FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
				FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
				FCGX_FPrintF(out,"Permission denied");
			}
			else
			{
				FCGX_FPrintF(out,"X-Accel-Redirect: /store/api/wishlist/%s\r\n",cgiPathInfo+CGIPATH_WISHLIST_LEN);
				FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
			}
		}
	}
	else
	{ // Default to a 403
		// cgiHeaderStatus(403,"Permission Denied");
		FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
		FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
		FCGX_FPrintF(out,"API Auth Unknown URL: %s\n",cgiPathInfo);
	}
	
	return 0;
}