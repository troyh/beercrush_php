.PHONY: DIRS

INCLUDES=-I/usr/include/boost -I/usr/include/libxml2 -I/home/troy/oak/ -I/usr/local/include/thrift/ -I/usr/local/include/thrudoc/

DIRS: $(WWW_DIR)/api/beer/ $(OBJ_DIR)/src/beer $(OBJ_DIR)/src/gen-cpp/


$(WWW_DIR)/api/beer/review.fcgi: $(OBJ_DIR)/src/beer/review.o $(OBJ_DIR)/src/gen-cpp/BeerCrush_types.o $(OBJ_DIR)/src/gen-cpp/BeerCrush_constants.o
	g++ $^ -lfcgi -lcgic_fcgi -lxml2 -lxslt -lmemcached -lgcrypt -loak -lboost_filesystem -L/home/troy/oak -lthrudoc -lthrucommon -lspread -o $@

$(OBJ_DIR)/src/beer/review.o: $(APP_DIR)/src/beer/review.cc 
	g++ $(INCLUDES) -c -o $@ $<

$(OBJ_DIR)/src/gen-cpp/BeerCrush_types.o: $(APP_DIR)/src/gen-cpp/BeerCrush_types.cpp 
	g++ $(INCLUDES) -c -o $@ $<

$(OBJ_DIR)/src/gen-cpp/BeerCrush_constants.o: $(APP_DIR)/src/gen-cpp/BeerCrush_constants.cpp
	g++ $(INCLUDES) -c -o $@ $<

$(WWW_DIR)/api/beer:
	if [ ! -d $@ ]; then  mkdir -p $@; fi;
	
$(OBJ_DIR)/src/beer:
	if [ ! -d $@ ]; then  mkdir -p $@; fi;

$(OBJ_DIR)/src/gen-cpp:
	if [ ! -d $@ ]; then  mkdir -p $@; fi;
