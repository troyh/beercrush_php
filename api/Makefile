.PHONY: ALL

SRC_DIR=../src
OBJ_DIR=/tmp

#vpath %.o $(OBJ_DIR)
vpath %.cc $(SRC_DIR)
vpath %.fcgi /var/www/beer/api

FCGI_PROGS=autocomplete.fcgi createlogin.fcgi login.fcgi logout.fcgi post.fcgi nearby.fcgi
ALL: $(FCGI_PROGS)

autocomplete.fcgi: $(OBJ_DIR)/autocomplete.o
	g++ $^ -lfcgi -lcgic_fcgi -o $(WWWDOCROOT)$(WWWRELPATH)$@

nearby.fcgi: $(OBJ_DIR)/nearby.o
	$(CXX) $^ -lfcgi -lcgic_fcgi -o $(WWWDOCROOT)$(WWWRELPATH)$@
	
edit.fcgi: $(OBJ_DIR)/edit.o $(OBJ_DIR)/beercrush.o
	$(CXX) $^ -lboost_filesystem-gcc43-mt-1_36 -L/usr/local/lib/OAK -loak -lxml2 -lmemcached -lgcrypt -lfcgi -lcgic_fcgi -o $(WWWDOCROOT)$(WWWRELPATH)$@

$(OBJ_DIR)/autocomplete.o: $(SRC_DIR)/autocomplete.cc
	$(CXX) -c -o $@ $^

$(OBJ_DIR)/nearby.o: $(SRC_DIR)/nearby.cc
	$(CXX) -c -o $@ $^

$(OBJ_DIR)/edit.o: $(SRC_DIR)/edit.cc
	$(CXX) -I/usr/local/include/boost-1_36 -I/usr/include/libxml2 -c -o $@ $^
	
$(OBJ_DIR)/beercrush.o: $(SRC_DIR)/beercrush.cc
	$(CXX) -I/usr/local/include/boost-1_36 -I/usr/include/libxml2 -c -o $@ $^

createlogin.fcgi: libapp.so
	-killall $(FCGI_PROGS)

login.fcgi: libapp.so

logout.fcgi: libapp.so
	-killall $(FCGI_PROGS)

post.fcgi: libapp.so
	-killall $(FCGI_PROGS)

libapp.so: $(OBJ_DIR)/libapp.o
	g++ -shared  $^ -lboost_filesystem-gcc43-mt-1_36 -L/usr/local/lib/OAK -loak -lxml2 -lcgic -o $(WWWDOCROOT)/$(WWWRELPATH)/$@
	
$(OBJ_DIR)/libapp.o: $(SRC_DIR)/libapp.cc
	$(CXX) -I/usr/local/include/boost-1_36 -I/usr/include/libxml2 -c -o $(OBJ_DIR)/$@ $^
	