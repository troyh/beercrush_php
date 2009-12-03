include ../Makefile.rules

FCGI_PROGS=autocomplete.fcgi nearby.fcgi nearby_beer.fcgi

ALL: $(FCGI_PROGS)
	$(MAKE) -C auth/

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
	$(MAKE) -C phpinc/ install;
	if /etc/init.d/supervisor status > /dev/null; then sudo /etc/init.d/supervisor stop; fi
	$(MAKE) -C auth/ install;
	echo "Copying FCGI programs: $(FCGI_PROGS) to $(WWW_DIR)/api";
	cp $(FCGI_PROGS) $(WWW_DIR)/api/
	sudo /etc/init.d/supervisor start;
	