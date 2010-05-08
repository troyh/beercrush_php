<?php
// header("Cache-Control: none"); // Debug only

define(PAGE_SIZE,50);
$GIT_WORK_TREE="/var/local/BeerCrush/git";


$doc_path=str_replace(':','/',$_GET['doc_id']);

if (isset($_GET['version'])) {
	header("Content-Type: application/json; charset=utf-8");
	passthru("git --work-tree=$GIT_WORK_TREE --git-dir=$GIT_WORK_TREE/.git/ show ".$_GET['version']);
}
else {
	if (empty($_GET['after']) && empty($_GET['before'])) { // Both are unspecified
		$after_time=date('U',strtotime(date('Y/m/d 00:00:00')));
		$before_time=time();
	}
	else if (empty($_GET['after'])) { // Only before is specified
		$before_time=strtotime($_GET['before']);
		$after_time=$before_time-(60*60*24);
	}
	else { // Only after is specified
		$after_time=strtotime($_GET['after']);
		$before_time=$after_time+(60*60*24);
	}
	
	$after=date('Y/m/d H:i:s',$after_time);
	$before=date('Y/m/d H:i:s',$before_time);
	
	if (empty($_GET['pg']) || !is_numeric($_GET['pg']))
		$skip=0;
	else
		$skip=($_GET['pg']-1)*PAGE_SIZE;
		
	// print "After=$after Before=$before Skip=$skip";
	
	exec("git --work-tree=$GIT_WORK_TREE --git-dir=$GIT_WORK_TREE/.git/ log --unified=0 --full-index --after='".$after."' --before='".$before."' --max-count=".PAGE_SIZE." --skip=$skip $GIT_WORK_TREE/$doc_path",$git_output,$retcode);
	// print "<pre>";print_r($git_output);print "</pre>";exit;

	$output=new stdClass;
	$output->id=$_GET['doc_id'];
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
			else if (preg_match('/^---\sa\/(.*)$/',$ln,$matches)) {
				if (!is_null($commit))
					$commit->docid=str_replace('/',':',$matches[1]);
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