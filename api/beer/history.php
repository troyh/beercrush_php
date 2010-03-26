<?php
header("Cache-Control: none"); // Debug only

$GIT_WORK_TREE="/var/local/BeerCrush/git";

$beerdoc_path=str_replace(':','/',$_GET['beer_id']);

exec("git --work-tree=$GIT_WORK_TREE --git-dir=$GIT_WORK_TREE/.git/ log --unified=0 --full-index $GIT_WORK_TREE/$beerdoc_path",$git_output,$retcode);

$output=new stdClass;
$output->id=$_GET['beer_id'];
$output->changes=array();
$commit=null;

if ($retcode==0) {
	// print join("<br />",$git_output);
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
		else if (preg_match('/^[+-]\s/',$ln,$matches)) {
			
		}
	}
}

if (!is_null($commit))
	$output->changes[]=$commit;

header("Content-Type: application/json; charset=utf-8");
print json_encode($output);

?>