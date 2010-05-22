include ../Makefile.rules

FCGI_PROGS=nearby.fcgi nearby_beer.fcgi

build: $(FCGI_PROGS)
	make -C auth/
	make -C external/cgic/
	make -C OAK/

nearby.fcgi: nearby.o external/cgic/libcgic.a
	$(CXX) $^ -lfcgi -o $@

nearby_beer.fcgi: nearby_beer.o external/cgic/libcgic.a
	$(CXX) $^ -lfcgi -o $@

external/cgic/libcgic.a:
	$(MAKE) -C external/cgic/ libcgic.a
	
clean:
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))

