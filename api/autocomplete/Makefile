include ../../Makefile.rules

build: autocomplete.fcgi

autocomplete.fcgi: autocomplete.o ../../src/external/cgic/libcgic.a
	g++ $^ -lfcgi -o $@

../../src/external/cgic/libcgic.a:
	$(MAKE) -C ../../src/external/cgic/ libcgic.a

clean:
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))

local_install: /var/local/BeerCrush/meta/autocomplete_names.tsv /var/local/BeerCrush/meta/autocomplete_styles.tab

/var/local/BeerCrush/meta/autocomplete_names.tsv:
	./autocomplete_list > $@

/var/local/BeerCrush/meta/autocomplete_styles.tab:
	bash autocomplete_styles > $@
