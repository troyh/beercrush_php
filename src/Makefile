include ../Makefile.rules

FCGI_PROGS=autocomplete.fcgi nearby.fcgi nearby_beer.fcgi

ALL: $(FCGI_PROGS)
	$(RECURSIVE_MAKE)

autocomplete.fcgi: autocomplete.o external/cgic/libcgic.a
	g++ $^ -lfcgi -o $@

nearby.fcgi: nearby.o external/cgic/libcgic.a
	$(CXX) $^ -lfcgi -o $@

nearby_beer.fcgi: nearby_beer.o external/cgic/libcgic.a
	$(CXX) $^ -lfcgi -o $@

external/cgic/libcgic.a:
	$(MAKE) -C external/cgic/ libcgic.a
	
clean:
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))

install: SAFETY_CHECK
	$(RECURSIVE_MAKE)
	@./install.sh $(FCGI_PROGS)
