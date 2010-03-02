#!/usr/bin/php
<?php
require_once('../jobs.class.php');

$job_number=0;
$completed_jobs=0;

if (empty($argv[1]))
	$job_limit=100;
else
	$job_limit=$argv[1];

$oak=new OAK('/etc/BeerCrush/webapp.conf');
$oakjobs=new OAKJobs($oak);

if ($oakjobs->join_group()===FALSE) {
	print "join_group() failed\n";
}
else{
	
	// Wait for someone else to join group
	print "Waiting for others to join group...\r";
	while ($oakjobs->group_member_count() < 2) {
	}
	print date('r').": Starting $job_limit jobs.\n";
	
	// Create a random number of jobs
	create_more_jobs();
	
	do {
		// print "# jobs left: ".$oakjobs->job_count()."\n";
		// print "Waiting for a job...\n";
		$job=$oakjobs->next_job(5*60); // Give myself 5 minutes to do the job
		if ($job===FALSE) {
			create_more_jobs();
		}
		else {
			// Do job
			print "Got a job: ".$job."\n";
		
			// sleep(rand(0,3)); // Pretend to do the job
			$completed_jobs++;
		
			// Tell everyone I did it
			// print "Finished job $job\n";
			$oakjobs->job_done($job);
		}
	}
	while ($job_number < $job_limit || $oakjobs->job_count());
	
	print date('r').": Done\n";
	
	$oakjobs->leave_group();
}

// Give statistics
print "I created ".$job_number." jobs and completed ".$completed_jobs." jobs\n";

function create_more_jobs() {
	global $oakjobs;
	global $job_number;
	global $job_limit;
	
	$pid=getmypid();
	
	for ($i=0,$j=rand(1,10);$i<$j && $job_number<$job_limit;++$i) {
		++$job_number;
		$oakjobs->create_job($pid.'-'.$job_number);
		// print "Created job ".$pid.'-'.$job_number."\n";
	}
	
}

?>