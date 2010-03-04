#!/usr/bin/php
<?php
require_once('../jobs.class.php');

$completed_jobs=0;

$oak=new OAK('/etc/BeerCrush/webapp.conf');
$oakjobs=new OAKJobs($oak,'testjobs');
$oakjobs->set_message_callback('message_callback');
$oakjobs->gimme_jobs('job_callback');
// Give statistics
print "I completed ".$completed_jobs." jobs\n";

function job_callback($oakjobs,$job) {
	global $completed_jobs;
	print "Job:";
	print_r($job);
	print "\n";
	++$completed_jobs;
	return TRUE; // I did it
}

function message_callback($oakjobs,$msg) {
	print "Message:";
	print_r($msg);
	print "\n";
}
?>