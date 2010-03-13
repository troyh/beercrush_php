<?php

function JSONdiff($a,$b,$prefix='',&$changes)
{
	if (gettype($a)!=gettype($b))
	{
		// print "Changed $prefix: ".json_encode($a).'->'.json_encode($b)."\n";
		$changes=array(
			'_diff' => array()
		);
		
		if (!is_null($a))
			$changes['_diff']['old']=$a;
		if (!is_null($b))
			$changes['_diff']['new']=$b;
	}
	else if (is_scalar($a) && is_scalar($b))
	{
		if ($a!=$b)
		{
			$changes=array(
				'_diff' => array(
					'old' => $a,
					'new' => $b
				)
			);
			// print "-\"$prefix\": $a\n";
			// print "+\"$prefix\": $b\n";
		}
	}
	else if (is_array($a)) {

		// Get union of keys
		$all_keys=array();
		foreach ($a as $k=>$v)
		{
			$all_keys[]=$k;
		}
		foreach ($b as $k=>$v)
		{
			if (!in_array($k,$all_keys))
				$all_keys[]=$k;
		}

		if (!empty($prefix))
			$prefix.='.';

		foreach ($all_keys as $k)
		{
			if ($a[$k]!==$b[$k])
			{
				JSONdiff($a[$k],$b[$k],$prefix.$k,$changes[$k]);
			}
		}
	}
	else if (is_object($a))
	{
		// Get union of keys
		$all_keys=array();
		foreach ($a as $k=>$v)
		{
			$all_keys[]=$k;
		}
		foreach ($b as $k=>$v)
		{
			if (!in_array($k,$all_keys))
				$all_keys[]=$k;
		}

		if (!empty($prefix))
			$prefix.='.';

		foreach ($all_keys as $k)
		{
			if (!isset($b->$k))
			{
				$changes[$k]=array(
					'_diff' => array(
						'old' => $a->$k
					)
				);
				// print "$prefix$k\t\t{$a->$k}\t\n";
				// print "-\"$prefix$k\": ";print json_encode($a->$k);print "\n";
			}
			else if (!isset($a->$k))
			{
				$changes[$k]=array(
					'_diff' => array(
						'new' => $b->$k
					)
				);
				// print "+\"$prefix$k\": ";print json_encode($b->$k);print "\n";
			}
			else if ($a->$k!==$b->$k)
			{
				JSONdiff($a->$k,$b->$k,$prefix.$k,$changes[$k]);
			}
		}
	}
}

?>