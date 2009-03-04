#!/usr/bin/perl -w

use File::Basename;

$dir=shift;
mkdir "$dir";

while (<>)
{
	if (/^--/)
	{
		close OUT;

		my $fname=$';
		chomp($fname);
		$fname.=".xml";
		my $dirname=dirname("$dir/$fname");
		mkdir "$dirname";
		
		print "$dir/$fname\n";
		open(OUT,">$dir/$fname") || die "Can't open $dir/$fname";
	}
	else
	{
		print OUT;
	}
}

close OUT;
