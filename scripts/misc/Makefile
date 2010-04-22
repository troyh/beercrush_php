include ../../Makefile.rules

ALL: SAFETY_CHECK
	$(RECURSIVE_MAKE);

install: SAFETY_CHECK ALL $(BC_BIN_DIR)/autocomplete_list $(BC_BIN_DIR)/autocomplete_update
	$(RECURSIVE_MAKE);

$(BC_BIN_DIR)/autocomplete_list: autocomplete_list
	cp $^ $@

$(BC_BIN_DIR)/autocomplete_update: autocomplete_update
	cp $^ $@

check:
	$(RECURSIVE_MAKE);

setup:
	$(RECURSIVE_MAKE);
