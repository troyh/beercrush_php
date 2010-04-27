include ../Makefile.rules

FCGI_PROGS=autocomplete.fcgi

build: $(FCGI_PROGS)

autocomplete.fcgi: autocomplete.o ../src/external/cgic/libcgic.a
	g++ $^ -lfcgi -o $@

../src/external/cgic/libcgic.a:
	$(MAKE) -C ../src/external/cgic/ libcgic.a

clean:
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))

