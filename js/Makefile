include ../Makefile.rules

ALL: mini/ $(addprefix mini/,$(wildcard *.js))
	$(RECURSIVE_MAKE);

clean:
	rm -rf mini/
	
mini/:
	mkdir $@

mini/%.js: %.js
	yui-compressor $< > $@

install: SAFETY_CHECK
	sh ./install.sh 
	$(RECURSIVE_MAKE);
