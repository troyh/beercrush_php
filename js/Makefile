include ../Makefile.rules

build: beerstyles.js mini/ $(addprefix mini/,$(wildcard *.js))

beerstyles.js:
	php beerstyles_data > $@
	
clean:
	rm -rf mini/
	
mini/:
	mkdir $@

mini/%.js: %.js
	yui-compressor $< > $@
