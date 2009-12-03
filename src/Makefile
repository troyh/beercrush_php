include ../Makefile.rules

WWW_DIR:=$(shell php -r '$$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $$cfg->file_locations->WWW_DIR."\n";')

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
	$(MAKE) -C auth/ install;
	sudo /etc/init.d/supervisor stop;
	cp $(FCGI_PROGS) $(WWW_DIR)/api/
	sudo /etc/init.d/supervisor start;
	