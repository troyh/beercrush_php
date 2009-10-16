#!/bin/bash

function usage()
{
	echo "Usage: $0 NumbersFile [SheetName GridName]";
	echo;
	exit;
}

if [ -z "$1" ]; then
	usage;
fi

NUMBERS_FILE=$1

if [ ! -f $NUMBERS_FILE ]; then
	echo "$NUMBERS_FILE doesn't exist";
	exit;
fi

XML_FILE=`basename $NUMBERS_FILE .numbers`.xml

# Unzip the Numbers file to get the XML file
unzip -q $NUMBERS_FILE index.xml
mv index.xml $XML_FILE


if [ -z "$2" ]; then

echo "Available worksheets:";
echo;

xmlstarlet sel -N ls="http://developer.apple.com/namespaces/ls" -N sf="http://developer.apple.com/namespaces/sf" -N sfa="http://developer.apple.com/namespaces/sfa" -t \
	-m '/ls:document/ls:workspace-array/ls:workspace' \
	-m 'ls:page-info/sf:layers/sf:layer/sf:drawables/sf:tabular-info/sf:tabular-model' \
	-m 'sf:grid' \
	-v '../../../../../../../@ls:workspace-name' -o ':' -v '../@sf:name' -o ':' -v '@sf:numcols' -o ' columns, ' -v '@sf:numrows' -o ' rows' -n \
	$XML_FILE

elif [ -z "$3" ]; then
	usage;
else
	
	SHEET_NAME=$2
	GRID_NAME=$3
	
	OUTPUT_XML_FILE=`echo $2|sed -e 's/[^a-z0-9]/-/gi'`"-"`echo $3|sed -e 's/[^a-z0-9]/-/gi'`".xml"
	
	xmlstarlet sel -N ls="http://developer.apple.com/namespaces/ls" -N sf="http://developer.apple.com/namespaces/sf" -N sfa="http://developer.apple.com/namespaces/sfa" -t \
		-c "/ls:document/ls:workspace-array/ls:workspace[@ls:workspace-name=&quot;$SHEET_NAME&quot;]/ls:page-info/sf:layers/sf:layer/sf:drawables/sf:tabular-info/sf:tabular-model[@sf:name=&quot;$GRID_NAME&quot;]" $XML_FILE > $OUTPUT_XML_FILE

	# NUM_COLS=`xmlstarlet sel -N ls="http://developer.apple.com/namespaces/ls" -N sf="http://developer.apple.com/namespaces/sf" -N sfa="http://developer.apple.com/namespaces/sfa" -t \
	# 	-v "/ls:document/ls:workspace-array/ls:workspace[@ls:workspace-name=&quot;$SHEET_NAME&quot;]/ls:page-info/sf:layers/sf:layer/sf:drawables/sf:tabular-info/sf:tabular-model[@sf:name=&quot;$GRID_NAME&quot;]/sf:grid/@sf:numcols" $XML_FILE`
		
	# echo "NUM_COLS=$NUM_COLS";exit;

	# xmlstarlet sel -N ls="http://developer.apple.com/namespaces/ls" -N sf="http://developer.apple.com/namespaces/sf" -N sfa="http://developer.apple.com/namespaces/sfa" -t \
	# -m "/ls:document/ls:workspace-array/ls:workspace[@ls:workspace-name=&quot;$SHEET_NAME&quot;]/ls:page-info/sf:layers/sf:layer/sf:drawables/sf:tabular-info/sf:tabular-model[@sf:name=&quot;$GRID_NAME&quot;]/sf:grid/sf:datasource" \
	# -m 'sf:t|sf:g|sf:n|sf:o' \
	# -i 'name()=&quot;sf:t&quot;' -v 'concat(sf:ct/@sfa:s,sf:ct/sf:sn,sf:ct/sf:so/child::*)' -n -b \
	# -i 'name()=&quot;sf:n&quot;' -v '@sf:v' -n -b \
	# -i 'name()=&quot;sf:g&quot;' -i '@sf:ct &gt; 1' -o 'TOTAL_NEWLINES:' -v '@sf:ct' -b -n -b \
	# -i 'name()=&quot;sf:o&quot;' -n -b \
	# $XML_FILE \
	# | sed -e '$d' \
	# | perl -ne 'if (/^TOTAL_NEWLINES:(\d+)/) { $n=$1; while ($n--) {print "\n";}} else { print; }' \
	# | sed -e 's/^/<c>/' -e 's/$/<\/c>/' -e "0~${NUM_COLS}s/$/<\/rec><rec>/" -e '1s/^/<sheet><rec>/' -e '$s/$/<\/rec><\/sheet>/' \
	# | sed -e '$s/<rec><\/rec>//' > $OUTPUT_XML_FILE
	
	echo "Created $OUTPUT_XML_FILE";
		
fi

rm -f $XML_FILE
