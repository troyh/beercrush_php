.PHONY: ALL

SRC_DIR=../src
OBJ_DIR=/tmp

vpath %.o $(OBJ_DIR)
vpath %.cc $(SRC_DIR)
vpath %.fcgi /var/www/beer/api

FCGI_PROGS=autocomplete.fcgi createlogin.fcgi login.fcgi logout.fcgi post.fcgi nearby.fcgi
ALL: $(FCGI_PROGS)

autocomplete.fcgi: autocomplete.o
	g++ $^ -lfcgi -lcgic_fcgi -o $(WWWDOCROOT)$(WWWRELPATH)$@

nearby.fcgi: nearby.o
	g++ $^ -lfcgi -lcgic_fcgi -o $(WWWDOCROOT)$(WWWRELPATH)$@
	-killall $@

autocomplete.o: autocomplete.cc
	g++ -c -o $(OBJ_DIR)/$@ $^

nearby.o: nearby.cc
	g++ -c -o $(OBJ_DIR)/$@ $^
	
createlogin.fcgi: libapp.so
	-killall $(FCGI_PROGS)

login.fcgi: libapp.so
	-killall $(FCGI_PROGS)

logout.fcgi: libapp.so
	-killall $(FCGI_PROGS)

post.fcgi: libapp.so
	-killall $(FCGI_PROGS)

libapp.so: libapp.o
	g++ -shared  $^ -lboost_filesystem-gcc43-mt-1_36 -L/usr/local/lib/OAK -loak -lxml2 -lcgic -o $(WWWDOCROOT)/$(WWWRELPATH)/$@
	
libapp.o: libapp.cc
	$(CXX) -I/usr/local/include/boost-1_36 -I/usr/include/libxml2 -c -o $(OBJ_DIR)/$@ $^
	