#!/bin/bash

cat - > /tmp/ignore.sed <<EOF
/^src\/3rdparty\//d
/^js\/jquery-1.3.1.js:/d
/^makeconf.sh:/d
/^spread\/web:/d
/^src\/beer\/review.cc:/d
/^src\/edit.cc:/d
/^src\/libapp.cc:/d
/^src\/onchange\/brewery:/d
/^src\/phpinc\/Beer.class.php:/d
/^TODOs.sh:/d
/^tools\/csv2xml.pl:/d
EOF

grep -rl TODO: * |grep -ve '\/\.' |xargs grep TODO: | sed -e 's/^\([^:]\+\):.*TODO:\(.*\)/\1:\2/' | sed --file=/tmp/ignore.sed > /tmp/TODOs.list

total=$(wc -l /tmp/TODOs.list);
total=${total%% *};
echo $total TODOs;
echo;
cat /tmp/TODOs.list;

rm -f /tmp/ignore.sed /tmp/TODOs.list;
