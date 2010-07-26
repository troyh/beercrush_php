#!/bin/bash

data_dir="/var/local/BeerCrush/TODOs";
if [ ! -d "$data_dir" ]; then
	mkdir -p "$data_dir";
fi

# Name the current TODOs list file
newest_list="$data_dir/TODOs.$(date +%Y-%m-%d)";
# Get most-recent TODOs list file
previous_list=$(ls $data_dir/TODOs.* | sort -r | grep -v "$newest_list" | head -n 1);

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
/^php\/include\/OAK\/s3-php5-curl\/S3.php/d
EOF
#cat /tmp/ignore.sed;exit;

grep -rl TODO: $dir/* |
sed -e "s|^$dir/||" |
sed --file=/tmp/ignore.sed |
sed -e "s|^|$dir/|" |
xargs grep TODO: |
sed -e "s|^$dir/||" -e 's/^\([^:]\+\):.*TODO:\(.*\)/\1:\2/' > "$newest_list";

total=$(wc -l "$newest_list");
total=${total%% *};
echo $total TODOs;
echo;
if [ -f "$previous_list" ]; then
	echo "New:";
	diff -u "$previous_list" "$newest_list" | grep -e '^\+[^+]' |  sed -e 's/^+//'
	echo;
fi
echo "Current:";
cat "$newest_list";

rm -f /tmp/ignore.sed;

# Remove older TODOs list files (i.e., just keep the 2 most-recent)
ls $data_dir/TODOs.* | sort -r | sed -e '1,2d' | while read oldfile; do
	rm -f $oldfile;
done
