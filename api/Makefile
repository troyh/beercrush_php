include ../Makefile.rules

FCGI_PROGS=autocomplete.fcgi

ALL: $(FCGI_PROGS)
	$(RECURSIVE_MAKE)

autocomplete.fcgi: autocomplete.o ../src/external/cgic/libcgic.a
	g++ $^ -lfcgi -o $@

../src/external/cgic/libcgic.a:
	$(MAKE) -C ../src/external/cgic/ libcgic.a

clean:
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))

install: SAFETY_CHECK
	@./install.sh
	$(RECURSIVE_MAKE)
