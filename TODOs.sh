#!/bin/bash

dir=${0%/*};

cat - > /tmp/ignore.sed <<EOF
/\/\./d
/^src\/3rdparty\//d
/^js\/jquery-1.3.1.js$/d
/^makeconf.sh$/d
/^spread\/web/d
/^src\/beer\/review.cc$/d
/^src\/edit.cc$/d
/^src\/libapp.cc$/d
/^src\/onchange\/brewery/d
/^src\/phpinc\//d
/^TODOs.sh$/d
/^tools\/csv2xml.pl/d
/^js\/excanvas.js/d
/^js\/excanvas\//d
/^js\/bt-0.9.5-rc1\//d
EOF
#cat /tmp/ignore.sed;exit;

grep -rl TODO: $dir/* |
sed -e "s|^$dir/||" |
sed --file=/tmp/ignore.sed |
sed -e "s|^|$dir/|" |
xargs grep TODO: |
sed -e "s|^$dir/||" -e 's/^\([^:]\+\):.*TODO:\(.*\)/\1:\2/' > /tmp/TODOs.list

total=$(wc -l /tmp/TODOs.list);
total=${total%% *};
echo $total TODOs;
echo;
cat /tmp/TODOs.list;

rm -f /tmp/ignore.sed /tmp/TODOs.list;
