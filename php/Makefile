include ../Makefile.rules

install: SAFETY_CHECK
	rsync --recursive --delete ./ $(WWW_DIR)/php/;
	R=`svnversion -n`; sed --in-place -e "s/<\\!--\\s*SVNVERSION\\s*-->/$$R/" $(WWW_DIR)/php/index.php $(WWW_DIR)/php/footer.php

