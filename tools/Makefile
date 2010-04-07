include ../Makefile.rules

# The list of tools to put in the bin dir
TOOLS=db_dump \
 	jsonpath \
 	dbchanges2git

ALL:
	$(RECURSIVE_MAKE)

install: SAFETY_CHECK $(addprefix $(BC_BIN_DIR)/,$(TOOLS))
	$(RECURSIVE_MAKE)

$(addprefix $(BC_BIN_DIR)/,$(TOOLS)): $(TOOLS)
	cp $(@F) $@
	chmod +x $@
