.PHONY: ALL

SRC_DIR=../src
OBJ_DIR=/tmp

vpath %.o $(OBJ_DIR)
vpath %.cc $(SRC_DIR)

ALL: autocomplete.fcgi

autocomplete.fcgi: autocomplete.o
	g++ $^ -lfcgi -lcgic_fcgi -o $(WWWDOCROOT)$(WWWRELPATH)$@

autocomplete.o: autocomplete.cc
	g++ -c -o $(OBJ_DIR)/$@ $^