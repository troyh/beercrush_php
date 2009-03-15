#include <exception>
#include <string>

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
