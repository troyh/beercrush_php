.PHONY: FOOTERS install

FOOTERS:
	R=`svnversion -n`; sed --in-place -e "s/<\\!--\\s*SVNVERSION\\s*-->/$$R/" index.php footer.php
	