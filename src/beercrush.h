#include <exception>
#include <string>

// Boost stuff
#include <boost/filesystem.hpp>


#define BEERCRUSH_MAX_PLACE_ID_LEN 256

class BeerCrushException : public std::exception
{
	std::string m_msg;
public:
	BeerCrushException(const char* msg)	: m_msg(msg) {}
	~BeerCrushException() throw() {}
	const char* what() const throw() { return m_msg.c_str(); }
};


struct EDITABLE_FIELDS
{
	const char* xpath;
	bool (*validate_func)(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
	
	static int find(const char* xpath, EDITABLE_FIELDS* fields, size_t fields_count);

	static bool validate_yesno(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
	static bool validate_uinteger(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
	static bool validate_text(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
	static bool validate_phone(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
	static bool validate_uri(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
	static bool validate_float(const char* s, bool* useOrigVal, char* newVal, size_t newValSize);
};

struct EDITABLE_DOCTYPES
{
	const char* pathinfo;
	const char* id_field;
	const char* xmldirpath;
	EDITABLE_FIELDS* editable_fields;
	size_t editable_fields_count;

	static int find(const char* pathinfo, EDITABLE_DOCTYPES* types, size_t types_count);
};

void editDoc(boost::filesystem::path xml_file,EDITABLE_FIELDS* editable_fields, size_t editable_fields_count, const char* id_string, const char* xpath_prefix);
