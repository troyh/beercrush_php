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
		if ($#cols>23)
		{
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
						# Replace double quotes with a single one
						$cols[$j]=~s/""/"/;
					
						if ($cols[$j]=~/"$/)
						{
							$cols[$j]=~s/"$//;
							print ",".$cols[$j]."\t";
							$i=$j;
							$j=$#cols+1;
						}
						else
						{
							print ",".$cols[$j];
						}
					
					}
				}
				else
				{
					print $cols[$i]."\t";
				}
			}
		}
		else
		{
			print join("\t",@cols);
		}
		print "\n";
	}
	
}
