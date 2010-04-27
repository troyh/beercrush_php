include ../../Makefile.rules

ALL: mini/ $(addprefix mini/,$(wildcard *.js))

clean:
	rm -rf mini/
	
mini/:
	mkdir $@

mini/%.js: %.js
	yui-compressor $< > $@
