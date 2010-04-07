<?php
header("Cache-Control: none"); // Debug only

$GIT_WORK_TREE="/var/local/BeerCrush/git";


$beerdoc_path=str_replace(':','/',$_GET['beer_id']);

if (isset($_GET['version'])) {
	header("Content-Type: application/json; charset=utf-8");
	passthru("git --work-tree=$GIT_WORK_TREE --git-dir=$GIT_WORK_TREE/.git/ show ".$_GET['version']);
}
else {
	exec("git --work-tree=$GIT_WORK_TREE --git-dir=$GIT_WORK_TREE/.git/ log --unified=0 --full-index $GIT_WORK_TREE/$beerdoc_path",$git_output,$retcode);

	$output=new stdClass;
	$output->id=$_GET['beer_id'];
	$output->changes=array();
	$commit=null;

	if ($retcode==0) {
		// print join("<br />",$git_output);exit;
		foreach ($git_output as $ln) {
			if (preg_match('/^commit\s+(.*)$/',$ln,$matches)) {
				if (!is_null($commit)) {
					$output->changes[]=$commit;
				}
				$commit=new stdClass;
				$commit->commit=$matches[1];
			}
			else if (preg_match('/^Date:\s*(.*)$/',$ln,$matches)) {
				if (!is_null($commit))
					$commit->date=$matches[1];
			}
			else if (preg_match('/^index\s([a-z0-9]+)\.\.([a-z0-9]+)/',$ln,$matches)) {
				if (!is_null($commit))
					$commit->index=$matches[2];
			}
			else if (preg_match('/^([+-])\s(.*)$/',$ln,$matches)) {
				if (!is_null($commit)) {
					if (!isset($commit->change))
						$commit->change=new stdClass;
					if ($matches[1]=='-')
						$oldornew='old';
					else
						$oldornew='new';

					$s=trim($matches[2]);
					$s=trim($s,',');
					// print "s=$s<br />";
					$d=json_decode('{'.$s.'}');
					if (!is_null($d)) {
						foreach ($d as $k=>$v) {
							$commit->change->$oldornew->$k=$v;
						}
					}
				}
			}
		}
	}

	if (!is_null($commit))
		$output->changes[]=$commit;

	// Remove anything that hasn't really changed
	foreach ($output->changes as $chg) {
		if (isset($chg->change->old)) {
			foreach ($chg->change->old as $k=>$v) {
				if (isset($chg->change->new->$k) && $chg->change->new->$k=$chg->change->old->$k) {
					unset($chg->change->old->$k);
					unset($chg->change->new->$k);
				}
			}
		}
	}

	// Remove empty old/new change lists
	foreach ($output->changes as $chg) {
		if (isset($chg->change->old)) {
			$n=0;
			foreach ($chg->change->old as $k=>$v) {++$n;}
			if ($n==0)
				unset($chg->change->old);
		}

		if (isset($chg->change->new)) {
			$n=0;
			foreach ($chg->change->new as $k=>$v) {++$n;}
			if ($n==0)
				unset($chg->change->new);
		}
	}

	header("Content-Type: application/json; charset=utf-8");
	print json_encode($output);
}


?>