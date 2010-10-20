include ../../Makefile.rules

FCGI_PROGS=nearby_locations.fcgi

build: $(FCGI_PROGS) ../../src/external/cgic/libcgic.a 

install: $(LOCALDATA_DIR)/meta/nearby_locations.txt

nearby_locations.fcgi: nearby_locations.o ../../src/external/cgic/libcgic.a
	$(CXX) $^ -lfcgi -o $@

../../src/external/cgic/libcgic.a:
	$(MAKE) -C ../../src/external/cgic/ libcgic.a
	
clean:
	rm -f $(FCGI_PROGS) $(patsubst %.fcgi,%.o,$(FCGI_PROGS))


$(LOCALDATA_DIR)/meta/nearby_locations.txt: $(BC_BIN_DIR)/update_location_data
	$(BC_BIN_DIR)/update_location_data -C /etc/BeerCrush/webapp.conf;
	
$(BC_BIN_DIR)/update_location_data: $(BC_BIN_DIR)/latlonfile $(BC_BIN_DIR)/beers
