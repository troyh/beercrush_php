include ../../Makefile.rules

FCGI_PROGS=api.fcgi

ALL: $(FCGI_PROGS)

api.fcgi: api.o ../external/cgic/libcgic.a
	g++ $^ -lfcgi -lcurl -ljansson -o $@

../external/cgic/libcgic.a:
	$(MAKE) -C external/cgic/ libcgic.a
	
clean: SAFETY_CHECK
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))

install: SAFETY_CHECK
	@./install.sh
	$(RECURSIVE_MAKE)
