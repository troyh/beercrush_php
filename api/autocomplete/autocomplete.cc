#include <fcgiapp.h>

extern "C"
{
#include "../../src/external/cgic/cgic.h"
}


#include <map>
#include <string>
#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fstream>




using namespace std;

const char* const dataFilename="/var/local/BeerCrush/meta/autocomplete_db.tsv";
const char* const stylesDataFilename="/var/local/BeerCrush/meta/autocomplete_styles.tab";
static time_t datafile_last_read=0;
static time_t styles_datafile_last_read=0;

// Searchable data structures
const char** searchable_names=NULL;
typedef enum { UNKNOWN=0, BEER=1, BREWERY=2, PLACE=4, STYLE=128 } TYPES;
TYPES *searchable_types=NULL;
size_t searchable_names_count=0;

// Styles list
size_t styles_count=0;
const char** styles_list=NULL;

bool readFile(const char* fname, size_t* count, const char*** names, TYPES** types)
{
	char* buf=0;
	const char** list=0;
	TYPES* list_types=0;
	size_t entries=0;
	
	struct stat statbuf;
	stat(fname,&statbuf);
	// TODO: use the file's time to determine whether to load the file again

	// Read the entire file into memory
	FILE* f=fopen(fname,"r");
	if (f)
	{
		buf=new char[statbuf.st_size+1];
		if (buf)
		{
			size_t n=fread(buf,sizeof(buf[0]),statbuf.st_size,f);
			buf[n]='\0';
		}
		
		fclose(f);
	}
	
	if (buf)
	{
		// Walk buf and count the newlines
		for (char* p=strchr(buf,'\n');p;p=strchr(p+1,'\n'))
		{
			*p='\0'; // change the newline to a null-terminator
			++entries;
		}
		
		if (entries)
		{
			list=new const char*[entries];
			list_types=new TYPES[entries];
			
			char* p=buf;
			for(size_t i = 0; i < entries; ++i)
			{
				list[i]=p;
				char* tab=strchr(p,'\t');
				
				for (p+=strlen(p)+1;*p=='\0';++p)
				{ // Skip to next line, just in case there's multiple null-terminators at the end
				}
				
				if (tab)
				{
					*tab='\0';
					++tab;
					if (!strcasecmp(tab,"beer"))
						list_types[i]=BEER;
					else if (!strcasecmp(tab,"brewery"))
						list_types[i]=BREWERY;
					else if (!strcasecmp(tab,"place"))
						list_types[i]=PLACE;
				}
				else
					list_types[i]=UNKNOWN;
				
			}
		}
	}
	
	*count=entries;
	*names=list;
	*types=list_types;
	
	datafile_last_read=time(0);
}

bool readStylesFiles(const char* fname, size_t* count, const char*** styles) {
	char* buf=0;
	const char** list=0;
	
	struct stat statbuf;
	stat(fname,&statbuf);
	// TODO: use the file's time to determine whether to load the file again

	// Read the entire file into memory
	FILE* f=fopen(fname,"r");
	if (f)
	{
		buf=new char[statbuf.st_size+1];
		if (buf)
		{
			size_t n=fread(buf,sizeof(buf[0]),statbuf.st_size,f);
			buf[n]='\0';
		}
		
		fclose(f);
	}
	
	size_t entries=0;
	if (buf)
	{
		// Count the number of lines so we can alloc enough space for the list of pointers
		for (char* p=strchr(buf,'\n');p;p=strchr(p+1,'\n'))
		{
			entries++;
		}
		
		// Alloc space for the list of pointers
		list=new const char*[entries];
		entries=0; // Start over
		if (list) {
			// Walk buf and turn newlines into nulls and record each string in list
			list[entries++]=buf; // The first is the beginning of the buffer
			for (char* p=strchr(buf,'\n');p;p=strchr(p+1,'\n'))
			{
				*p='\0'; // change the newline to a null-terminator
				if (*(p+1)) // The last time this should be the null-terminator at the end of buf, so don't add an entry
					list[entries++]=p+1;
			}
		}
	}
	
	*count=entries;
	*styles=list;
	
	styles_datafile_last_read=time(0);
}

extern "C" void fcgiInit() 
{
	// TODO: make it read from the config file and load the data straight from couchdb so the file location is not hardcoded
	// // Read the conf file
	// Config cfg("/etc/BeerCrush/BeerCrush.conf");
	// 
	// char fname[256];
	// strncpy(fname,cfg.get("DOC_DIR"),sizeof(fname));
	// fname[sizeof(fname)-1]='\0';
	// strncat(fname,"/meta/brewery/autocomplete_names.txt",sizeof(fname)-strlen(fname)-1);
	// fname[sizeof(fname)-1]='\0';
	
	/* Load brewery list into memory, it *must* be sorted */
	readFile(dataFilename,&searchable_names_count,&searchable_names,&searchable_types);
	readStylesFiles(stylesDataFilename,&styles_count,&styles_list);
}

extern "C" void fcgiUninit() 
{
	if (searchable_names)
		free(searchable_names);
	if (styles_list)
		free(styles_list);
}

void get_delimited_field(const char* str, char delimeter, size_t fieldnum, const char** start, size_t* len) {

	if (fieldnum==0) { // Means the entire string
		*start=str;
		*len=strlen(str);
	}
	else {
		*start=NULL;
		*len=0;
	
		const char* field_start=str;
		while (fieldnum>0) {
			const char* p=strchr(field_start,delimeter);
			--fieldnum;
			if (fieldnum==0) {
				*start=field_start;
				if (p)
					*len=p-field_start;
				else
					*len=strlen(field_start);
			}
			else if (!p)
				break;
			else
				field_start=p+1;
		}
	}
	
}

typedef void (*OUTPUT_USERFUNC)(const char* str,const char** output_str,size_t* output_len);

void style_output(const char* line, const char** output_str, size_t* output_len) {
	const char* p=strchr(line,' ');
	if (!p) {
		*output_str=line;
		*output_len=strlen(*output_str);
	}		
	else {
		*output_str=p+1;
		*output_len=strlen(*output_str);
	}
}

void autocomplete(FCGX_Stream* out, const char* query,size_t query_len,const char** list,TYPES* types, size_t count, int filtertype, bool bXMLOutput,size_t* fieldnum,size_t fieldnum_count,char delimeter,OUTPUT_USERFUNC userfunc)
{
	if (count==0)
		return;
	if (query_len==0)
		return;
		
	// Binary-search list
	size_t hi=count;
	size_t lo=hi>0?1:hi;
	// lo and hi are 1-based so that we can decrement lo to zero without wrapping around
	while (lo<=hi)
	{
		size_t mid=(hi+lo)/2;

		// Remember, mid is 1-based, so use mid-1 to reference array items
		int cmp=strncasecmp(query,list[mid-1],query_len);
		if (cmp<0)
		{
			hi=mid-1;
		}
		else if (cmp>0)
		{
			lo=mid+1;
		}
		else
		{
			// Match, go backwards until we find the first one that doesn't match
			do
			{
				--mid;
			}
			while (mid && strncasecmp(query,list[mid-1],query_len) == 0);
			
			// mid is now before the 1st that matches, so spit out the names until it no longer matches

			size_t limit=30; // Limit it to 30 results, more than that is unnecessary
			do
			{
				++mid;
				if (limit && (strncasecmp(query,list[mid-1],query_len)==0))
				{
					if (filtertype==0 || !types || (types[mid-1]&filtertype))
					{
						//
						// Output the line
						//
						if (userfunc) {
							const char* s;
							size_t len;
							userfunc(list[mid-1],&s,&len);
							if (s && len) {
								FCGX_FPrintF(out,"%.*s\n",len,s);
							}
						}
						else if (delimeter && fieldnum) {
							for (size_t i=0;fieldnum[i];++i) {
								const char* p;
								size_t p_len;
								get_delimited_field(list[mid-1],delimeter,fieldnum[i],&p,&p_len);
								if (p && p_len) {
									FCGX_FPrintF(out,"%.*s",p_len,p);
									if (fieldnum[i+1])
										FCGX_FPrintF(out,"%c",delimeter);
								}
							}
							FCGX_FPrintF(out,"\n");
						}
						else {
							if (bXMLOutput) {
								// TODO: use libxml2 to take care of XML entities
								FCGX_FPrintF(out,"<result>");
								FCGX_FPrintF(out,"<text>%s</text>",list[mid-1]);
								FCGX_FPrintF(out,"</result>");
							}
							else
								FCGX_FPrintF(out,"%s\n",list[mid-1]);
						}
						
						--limit;
					}
				}
				else
					break;
			}
			while (mid<count);
			break;
		}
	}
}

extern "C" int fcgiMain(FCGX_Stream *in,FCGX_Stream *out,FCGX_Stream *err,FCGX_ParamArray envp)
{
	// See if we should refresh the data (older than 1 hour)
	if (datafile_last_read < (time(0)-(60*60))) {
		free(searchable_names);
		searchable_names_count=0;
		readFile(dataFilename,&searchable_names_count,&searchable_names,&searchable_types);
	}

	if (styles_datafile_last_read < (time(0)-(60*60))) {
		free(styles_list);
		styles_count=0;
		readStylesFiles(stylesDataFilename,&styles_count,&styles_list);
	}
	
	bool bXMLOutput=false;

	char query[256]="";
	char dataset[32]="";
	char output[16]="";
	char types[16]="";

	cgiFormString("q",query,sizeof(query));
	cgiFormString("dataset",dataset,sizeof(dataset));
	cgiFormString("output",output,sizeof(output));

	int filtertype=0;
	if (*dataset)
	{
		if (!strcasecmp(dataset,"beers"))
			filtertype=BEER;
		else if (!strcasecmp(dataset,"breweries"))
			filtertype=BREWERY;
		else if (!strcasecmp(dataset,"places"))
			filtertype=PLACE;
		else if (!strcasecmp(dataset,"beersandbreweries"))
			filtertype=BEER|BREWERY;
	}

	if (!strcasecmp(output,"xml"))
		bXMLOutput=true;
	
	if (bXMLOutput)
	{
		FCGX_FPrintF(out,"Content-Type: text/xml; charset=utf-8\r\n\r\n");
		FCGX_FPrintF(out,"<results>");
	}
	else
		FCGX_FPrintF(out,"Content-Type: text/plain; charset=utf-8\r\n\r\n");

	if (!strcasecmp(dataset,"beerstyle")) {
		// FCGX_FPrintF(out,"Querying for beerstyle:%s\n",query);
		// Do the query for each term in the query
		
		const char* q_term=query,*qp=query;
		for (;*qp;++qp) {
			if (isspace(*qp)) {
				// FCGX_FPrintF(out,"term=%s\n",q_term);
			
				autocomplete(out,q_term,qp - q_term,styles_list,0,styles_count,0,false,NULL,0,'\0',style_output);
			
				// Skip to next term
				do
					++qp;
				while (*qp && isspace(*qp));
			
				q_term=qp;
			}
		}
		if (*q_term) {
			// FCGX_FPrintF(out,"term=%s\n",q_term);
			autocomplete(out,q_term,qp - q_term,styles_list,0,styles_count,0,false,NULL,0,'\0',style_output);
		}
	}
	else
	{
		autocomplete(out,query,strlen(query),searchable_names,searchable_types,searchable_names_count,filtertype,bXMLOutput,NULL,0,'\0',NULL);
	}

	if (bXMLOutput)
		FCGX_FPrintF(out,"</results>");
	
	return 0;
}

