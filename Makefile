.PHONY: INSTALL BEERPAGES BREWERYPAGES PLACEPAGES FORCE ALL-BREWERY-METADATA

# Install xmlstarlet
# Create /etc/BeerCrush/BeerCrush.conf
# Configure Apache
# 
# Make beer pages
# Make brewery pages
# Make places pages

INSTALL: /etc/BeerCrush/BeerCrush.conf BEERPAGES BREWERYPAGES PLACEPAGES

BEERPAGES: ALL-BREWERY-METADATA
	$(MAKE) -C html/beer ALL-BEER-PAGES

BREWERYPAGES:
	$(MAKE) -C html/brewery ALL-BREWERY-PAGES

PLACEPAGES:

ALL-BREWERY-METADATA:
	$(MAKE) -C metadata/brewery ALL-BREWERY-METADATA

/etc/BeerCrush/BeerCrush.conf: /etc/BeerCrush/ FORCE
	./makeconf.sh $@

/etc/BeerCrush/:
	sudo mkdir /etc/BeerCrush
