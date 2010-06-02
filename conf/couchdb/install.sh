#!/bin/bash

if ../../tools/iamservertype -q couchdb; then

	if [ ! -d /var/local/nginx-couchdb/ ]; then
		sudo mkdir /var/local/nginx-couchdb/;
		sudo chown www-data.www-data /var/local/nginx-couchdb/;
	fi

fi

COUCHDB_HOST=$(php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->couchdb->nodes[0];');
COUCHDB_DBNAME=$(php -r '$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));print $cfg->couchdb->database;');

for F in $(find design/ -maxdepth 1 -mindepth 1 -type d ! -name ".*"); do

	./make_json $F/*.js > $F.json;

done

design_changed=0;

# Put design docs in couchdb
for D in design/*.json; do
	DD=$(basename $D .json);
	
	if ! cat  design/$DD.json | php -r '$doc=json_decode(file_get_contents("php://stdin"));exit(is_null($doc)?1:0);'; then 
		echo "ERROR: $DD.json is not valid JSON"; 
	else
		# Get the document (removing _id and _rev)
		curl --silent http://$COUCHDB_HOST/$COUCHDB_DBNAME/_design/$DD | 
			php -r '$doc=json_decode(file_get_contents("php://stdin"));unset($doc->_id);unset($doc->_rev);print json_encode($doc);' > design/$DD.indb;
		# Get the _rev from the document
		REV=$(curl --silent http://$COUCHDB_HOST/$COUCHDB_DBNAME/_design/$DD | php -r '$doc=json_decode(file_get_contents("php://stdin"));print $doc->_rev;');
		# Get the actual doc into the non-tidy version
		cat design/$DD.json | php -r 'print json_encode(json_decode(file_get_contents("php://stdin")));' > design/$DD.tmp;

		# Compare the two docs
		if ! diff design/$DD.tmp design/$DD.indb > /dev/null; then
			design_changed=1;
			echo "$DD has changed, replacing $REV in couchdb";
			# Add the _rev
			cat design/$DD.json | php -r '$doc=json_decode(file_get_contents("php://stdin"));$doc->_rev=$argv[1];print json_encode($doc);' $REV > design/$DD.new;
			# Put the new version
			if ! curl --fail --silent -X PUT -d @design/$DD.new http://$COUCHDB_HOST/$COUCHDB_DBNAME/_design/$DD > /dev/null; then
				echo "ERROR: Failed to put new design document $DD";
			else
				# Purge the doc from the cache
				curl --silent http://$COUCHDB_HOST/purge/$COUCHDB_DBNAME/_design/$DD > /dev/null;
			fi

			rm design/$DD.new;
		fi

		rm design/$DD.indb design/$DD.tmp;
	fi
done

rm -f design/*.json;

if [ $design_changed -ne 0 ]; then
	# Restart couchdb
	# sudo /etc/init.d/couchdb stop;
	sudo /etc/init.d/couchdb restart;
fi
