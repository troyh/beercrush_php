#!/usr/bin/perl -w

while (<>)
{
	chomp;
	
	if (/^\s*$/) # ignore blank lines
	{
	}
	else
	{
		@cols=split(',');
		# print "Quotes here\n";
		for ($i=0;$i<=$#cols;++$i)
		{
			if ($cols[$i]=~/^"/)
			{
				# Remove initial quote
				$cols[$i]=~s/^"//;
				# Replace double quotes with a single one
				$cols[$i]=~s/""/"/;
				print $cols[$i];
				for ($j=$i+1;$j<=$#cols;++$j)
				{
					if ($cols[$j]=~/"""$/)
					{
						# Replace double quotes with a single one
						$cols[$j]=~s/""/"/g;

						$cols[$j]=~s/"$//;
						print ",".$cols[$j]."\t";
						
						$i=$j;
						$j=$#cols+1;
					}
					elsif ($cols[$j]=~/""$/)
					{
						# Replace double quotes with a single one
						$cols[$j]=~s/""/"/g;

						print ",".$cols[$j];
					}
					elsif ($cols[$j]=~/"$/)
					{
						# Replace double quotes with a single one
						$cols[$j]=~s/""/"/g;

						$cols[$j]=~s/"$//;
						print ",".$cols[$j]."\t";
						
						$i=$j;
						$j=$#cols+1;
					}
					else
					{
						# Replace double quotes with a single one
						$cols[$j]=~s/""/"/g;

						print ",".$cols[$j];
					}
				
				}
			}
			else
			{
				print $cols[$i]."\t";
			}
		}
		print "\n";
	}
	
}
