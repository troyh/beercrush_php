#include <fcgiapp.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <openssl/md5.h>
#include <libmemcached/memcached.h>
#include <jansson.h>

extern "C"
{
#include "../external/cgic/cgic.h"
}

size_t asshole_count=0;
char* asshole_text=NULL;
char** assholes=NULL;

memcached_st* memcached_pool=NULL;

bool is_an_asshole(const char* userid) {
	if (userid && userid[0]) {
		// Binary search assholes for this userid
		size_t lo=0,hi=asshole_count;
		while (lo < hi) {
			size_t mid=(hi-lo)/2;
			int c=strcmp(userid,assholes[mid]);
			if  (c==0)
				return true;

			if (c < 0)
				hi=mid;
			else
				lo=mid+1;
		}
	}
	
	return false;
}

bool login_is_trusted(const char* userid,char* correct_usrkey=NULL, char* actual_usrkey=NULL)
{
	bool isTrusted=false;
	
	// Get the secret from memcached
	if (userid && userid[0] && memcached_pool) {
		char mc_key[64]="loginsecret:";
		strncat(mc_key,userid,sizeof(mc_key)-strlen(mc_key));
		mc_key[sizeof(mc_key)-1]='\0';

		size_t vlen;
		memcached_return r;
		uint32_t flags=0;
		char* secret=memcached_get(memcached_pool,mc_key,strlen(mc_key),&vlen,&flags,&r);
		if (secret) {
			// Now get the MD5 sum of the user_id+secret+REMOTE_ADDR
			char str[256];
			snprintf(str,sizeof(str),"%s%s%s",userid,secret,cgiRemoteAddr);
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
			usrkey[0]='\0';
			cgiCookieString("usrkey",usrkey,sizeof(usrkey));
			if (!strlen(usrkey))
				cgiFormString("usrkey",usrkey,sizeof(usrkey));
			
			if (!strcmp(usrkey,md5sum_str))
			{
				isTrusted=true;
			}
			
			if (correct_usrkey)
				strcpy(correct_usrkey,md5sum_str);
			if (actual_usrkey)
				strcpy(actual_usrkey,usrkey);
			
			free(secret);
		}
	}
					
	return isTrusted;
}

extern "C" void fcgiInit() 
{
	// Get list of assholes (from /var/local/BeerCrush/assholes)
	const char* ASSHOLE_FILENAME="/var/local/BeerCrush/assholes";

	struct stat statinfo;
	if (stat(ASSHOLE_FILENAME,&statinfo)==0 && statinfo.st_size) {
		asshole_text=(char*)calloc(statinfo.st_size,sizeof(char));
		if (asshole_text) {

			FILE* fp=fopen(ASSHOLE_FILENAME,"r");
			if (fp) {
				size_t readlen=fread(asshole_text,sizeof(char),statinfo.st_size,fp);
				fclose(fp);
				
				if (readlen) {
					asshole_text[readlen-1]='\0';

					//
					// Make asshole list
					//

					// Count them first
					size_t total=0;
					char* pp,*p;
					for (p=asshole_text;(pp=strchr(p,'\n'))!=NULL;p=pp+1) {
						total++;
					}
					if (*p)
						++total; // the last one wasn't ended by a newline

					// Allocate space for the list
					assholes=(char**)calloc(total,sizeof(*assholes));
					if (assholes) {
			
						// Now start over, null-terminating them and make asshole list of them all
						for (p=asshole_text;(pp=strchr(p,'\n'))!=NULL;p=pp+1) {
							*pp='\0';
							assholes[asshole_count++]=p;
						}
						if (*p)
							assholes[asshole_count++]=p; // the last one wasn't ended by a newline
					
					}					
				}
			}
		}
	}
	
	// Create pool of memcache servers
	memcached_pool=memcached_create(NULL);
	if (memcached_pool) {
		// Get memcached server list from config file

		json_error_t error;
		json_t* cfg=json_load_file("/etc/BeerCrush/webapp.conf",&error);
		if (cfg) {
	        json_t* memcached=json_object_get(cfg,"memcached");
	        if (json_is_object(memcached)) {
                json_t* nodes=json_object_get(memcached,"servers");
                if (json_is_array(nodes)) {
					for (size_t n=0,m=json_array_size(nodes);n < m; ++n) {
                        json_t* node=json_array_get(nodes,n);
                        if (json_is_array(node)) {
							json_t* hostname=json_array_get(node,0);
							json_t* port=json_array_get(node,1);
							if (json_is_string(hostname) && json_is_integer(port)) {
								memcached_server_add(memcached_pool,json_string_value(hostname),json_integer_value(port));
							}
						}
					}
				}
			}

			json_decref(cfg);
		}
	}
	
}

extern "C" void fcgiUninit() 
{
	if (asshole_text)
		free(asshole_text);
	if (assholes)
		free(assholes);
	if (memcached_pool)
		memcached_free(memcached_pool);
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
	const int CGIPATH_BOOKMARKS_LEN=15;

	char user_id[256];
	user_id[0]='\0';
	cgiCookieString("userid",user_id,sizeof(user_id));
	if (!strlen(user_id)) {
		cgiFormString("userid",user_id,sizeof(user_id));
	}

	// TODO: all URLs (and their permission rules) should be read from a config file rather than hardcoded here
	// 
	// The config file could look something like this. May also want to consider writing a generic NGiNX module.
	// 
	// { "url": "/api/beer/edit"	, "validlogin": true},
	// { "url": "/api/beer/review"	, "validlogin": true},
	// { "url": "/api/brewery/edit", "validlogin": true},
	// { "url": "/api/place/edit"	, "validlogin": true},
	// { "url": "/api/place/review", "validlogin": true},
	// { "url": "/api/logout"		, "validlogin": true},
	// { "url": "/api/menu/edit"	, "validlogin": true},
	// { "url": "/api/user/edit"	, "validlogin": true},
	// { "url": "/api/user/fullinfo", "validlogin": true},
	// { "url": "/api/wishlist/edit", "validlogin": true},
	// { "url": "/api/beer/*"		},
	// { "url": "/api/beercolors"},
	// { "url": "/api/beers"},
	// { "url": "/api/beerstyles"},
	// { "url": "/api/breweries"},
	// { "url": "/api/brewery/*"},
	// { "url": "/api/createlogin"},
	// { "url": "/api/flavors"},
	// { "url": "/api/history/*"},
	// { "url": "/api/login"},
	// { "url": "/api/menu/*"},
	// { "url": "/api/photoset/*"},
	// { "url": "/api/place/*"},
	// { "url": "/api/places"},
	// { "url": "/api/restaurantcategories"},
	// { "url": "/api/review/beer/*"},
	// { "url": "/api/review/place/*"},
	// { "url": "/api/image/*"},
	// { "url": "/api/user/*"},
	// { "url": "/api/users"},
	// { "url": "/api/search"},
	// { "urlregex": "/api/wishlist/([^/]+)", "validlogin": true, "authtest": "$userid==$1" }
	
	// Reject assholes (users who are abusing their site privileges)
	if (is_an_asshole(user_id)) {
		FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
		FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
		FCGX_FPrintF(out,"Access prohibited (NAR)\n"); // NAR=No Asshole Rule
		return 0;
	}

	const char* const valid_login_urls[]={
		"/api/beer/review",
		"/api/place/review",
		"/api/logout",
		"/api/recommend/edit",
		"/api/user/edit",
		"/api/user/fullinfo",
		"/api/bookmarks",
		"/api/wishlist"
	};
	
	for (size_t i=0;i < (sizeof(valid_login_urls)/sizeof(valid_login_urls[0]));++i) {
		if (!strcmp(valid_login_urls[i],cgiPathInfo)) {

			if (!login_is_trusted(user_id)) {
				FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
				FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
				FCGX_FPrintF(out,"Login required\n");
			}
			else {
				FCGX_FPrintF(out,"X-Accel-Redirect: /store%s%s%s\r\n",cgiPathInfo,(!strlen(cgiQueryString)?"":"?"),cgiQueryString);
				FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
			}

			return 0;
		}
	}

	if ((!strncmp(cgiPathInfo,"/api/beer/",10) || !strncmp(cgiPathInfo,"/api/place/",11)) &&
		(strlen(cgiPathInfo)>16) && !strcmp(&cgiPathInfo[strlen(cgiPathInfo)-16],"/personalization")) {
		// Verify that the requesting user has access permissions to this personalization (i.e., it's their own document)
		if (!login_is_trusted(user_id)) {
			FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
			FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
			FCGX_FPrintF(out,"Permission denied (not trusted)\n");
		}
		else
		{
			FCGX_FPrintF(out,"X-Accel-Redirect: /store%s\r\n",cgiPathInfo);
			FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
		}
	}
	else if (!strncmp(cgiPathInfo,"/api/beer/",10) || /* These URLs are publicly-accessible */
		!strcmp(cgiPathInfo,"/api/brewery/edit") ||
		!strcmp(cgiPathInfo,"/api/beer/edit") ||
		!strcmp(cgiPathInfo,"/api/beercolors") ||
		!strcmp(cgiPathInfo,"/api/beers") ||
		!strcmp(cgiPathInfo,"/api/beerstyles") ||
		!strcmp(cgiPathInfo,"/api/breweries") ||
		!strncmp(cgiPathInfo,"/api/brewery/",13) ||
		!strncmp(cgiPathInfo,"/api/createlogin",16) ||
		!strcmp(cgiPathInfo,"/api/flavors") ||
		!strncmp(cgiPathInfo,"/api/flavor/",12) ||
		!strcmp(cgiPathInfo,"/api/forgotpassword") ||
		!strncmp(cgiPathInfo,"/api/history/",13) ||
		!strcmp(cgiPathInfo,"/api/login") ||
		!strncmp(cgiPathInfo,"/api/menu/",10) ||
		!strcmp(cgiPathInfo,"/api/menu/edit") ||
		!strncmp(cgiPathInfo,"/api/photoset/",14) ||
		!strncmp(cgiPathInfo,"/api/place/",11) ||
		!strcmp(cgiPathInfo,"/api/place/edit") ||
		!strcmp(cgiPathInfo,"/api/places") ||
		!strncmp(cgiPathInfo,"/api/recommend",14) ||
		!strcmp(cgiPathInfo,"/api/restaurantcategories") ||
		!strncmp(cgiPathInfo,"/api/review/beer",16) ||
		!strncmp(cgiPathInfo,"/api/review/place",17) ||
		!strncmp(cgiPathInfo,"/api/location/",14) ||
		!strncmp(cgiPathInfo,"/api/image/",11) ||
		!strncmp(cgiPathInfo,"/api/style/",11) ||
		!strncmp(cgiPathInfo,"/api/user/",10) ||
		!strncmp(cgiPathInfo,"/api/users",10) ||
		!strcmp(cgiPathInfo,"/api/search"))
	{
		FCGX_FPrintF(out,"X-Accel-Redirect: /store%s%s%s\r\n",cgiPathInfo,(!strlen(cgiQueryString)?"":"?"),cgiQueryString);
		FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
	}
	else if (!strncmp(cgiPathInfo,"/api/wishlist/",CGIPATH_WISHLIST_LEN))
	{
		// Verify that the requesting user has access permissions to this wishlist (i.e., it's their own wishlist)
		if (strcmp(user_id,cgiPathInfo+CGIPATH_WISHLIST_LEN))
		{
			FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
			FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
			FCGX_FPrintF(out,"Permission denied\n");
		}
		else
		{
			FCGX_FPrintF(out,"X-Accel-Redirect: /store/api/wishlist/%s\r\n",cgiPathInfo+CGIPATH_WISHLIST_LEN);
			FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
		}
	}
	else if (!strncmp(cgiPathInfo,"/api/bookmarks/",CGIPATH_BOOKMARKS_LEN))
	{
		// Verify that the requesting user has access permissions to this bookmarks list (i.e., it's their own bookmarks)
		if (strcmp(user_id,cgiPathInfo+CGIPATH_BOOKMARKS_LEN))
		{
			FCGX_FPrintF(out,"Status: 403 Permission denied\r\n");
			FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
			FCGX_FPrintF(out,"Permission denied");
		}
		else
		{
			FCGX_FPrintF(out,"X-Accel-Redirect: /store/api/bookmarks/%s\r\n",cgiPathInfo+CGIPATH_BOOKMARKS_LEN);
			FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");
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