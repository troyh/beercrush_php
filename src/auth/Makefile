.PHONY: ALL clean install

WWW_DIR:=$(shell php -r '$$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $$cfg->file_locations->WWW_DIR."\n";')

FCGI_PROGS=api.fcgi

ALL: $(FCGI_PROGS)

api.fcgi: api.o ../external/cgic/libcgic.a
	g++ $^ -lfcgi -lcurl -ljansson -o $@

../external/cgic/libcgic.a:
	$(MAKE) -C external/cgic/ libcgic.a
	
clean:
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))

install:
	supervisorctl stop authapi:
	cp $(FCGI_PROGS) $(WWW_DIR)/auth/
	supervisorctl start authapi:
