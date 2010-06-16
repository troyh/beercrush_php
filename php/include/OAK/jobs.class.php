<?php
require_once('oak.class.php');

class OAKJobs {
	private $oak=null;
	private $job_queue=array();
	private $msg_group=null;
	private $job_group=null;
	private $job_group_members=array();
	private $my_spread_name=null;
	private $message_callback=null;
	
	public function __construct($oak,$group) {
		$this->oak=$oak;
		$my_name='OAK-'.getmypid();

		if (!is_array($group))
			$this->msg_group=array($group);
		else
			$this->msg_group=$group;

		$this->job_group=basename($_SERVER['PHP_SELF']).'-jobq';
		
		if ($this->oak && $this->oak->spread_connect(4803,$my_name,TRUE)!==TRUE) {
			// TODO: do something
		}
		else if ($this->oak->spread_join($this->job_group)!==TRUE) {
			// TODO: do something
		}
		else {
			foreach ($this->msg_group as $g) {
				if ($this->oak->spread_join($g)!==TRUE) {
					// TODO: do something
					$this->oak->log('Unable to join Spread group '.$g,OAK::LOGPRI_ERR);
				}
			}
		}
	}

	public function __destruct() {
		if ($this->oak) {
			$this->oak->spread_leave($this->job_group);
			foreach ($this->msg_group as $g) {
				$this->oak->spread_leave($g);
			}
			$this->oak->spread_disconnect();
		}
	}
	
	public function getOAK() { return $this->oak; }

	private function message_loop($timeout=null) {
		while (($newmsg=$this->oak->spread_receive($timeout))!=FALSE) {
			if (OAK::IS_MEMBERSHIP_MESS($newmsg['service_type'])) { // A Spread membership message
				// print "Received a membership message. Members:".join(',',$newmsg['group_members'])."\n";
				if ($newmsg['group']==$this->job_group) { // It's for our group
					$this->job_group_members=$newmsg['group_members'];
					$this->my_spread_name=$newmsg['group_members'][$newmsg['current_index']];
				}
			}
			else if ($this->is_our_msg_group($newmsg['groups'])) { // A regular message
				$job=json_decode($newmsg['message']);
				if (is_null($job))
					$job=$newmsg['message'];
					
				if ($this->message_callback) {
					call_user_func($this->message_callback,$this,$job,$newmsg);
				}
				
				$this->create_job($job,$newmsg);
			}
			else if (in_array($this->job_group,$newmsg['groups'])) { // A job control message
				$job_queue_msg=json_decode($newmsg['message']);
				// print "Received a job control message:";print_r($job_queue_msg);print "\n";
				
				// Find this in our job_queue
				$idx=null;
				for ($i=0,$j=count($this->job_queue);$i<$j;++$i) {
					if ($this->job_queue[$i]->sha1key==$job_queue_msg->sha1key) {
						$idx=$i;
						break;
					}
				}
				
				if (is_null($idx)) { // Add a new job to the queue
					// print "Adding a job to queue: {$job_queue_msg->job}\n";
					$this->job_queue[]=$job_queue_msg;
				}
				else if ($job_queue_msg->completed) { // Delete it
					// print "Removing ".$this->job_queue[$idx]->job."\n";
					array_splice($this->job_queue,$idx,1);
				}
				else { // Update it
					// print "Updating job: {$job_queue_msg->sha1key}\n";
					foreach ($job_queue_msg as $k=>$v) {
						// print "$k=";print_r($v);print "\n";
						switch ($k) {
							case 'bid':
								// Check for a tie
									foreach ($this->job_queue[$idx]->bids as $member=>$bid) {
									if ($bid==$v) {
										// print "TIE!!\n";
										++$v; // Tie goes to first member
										reset($this->job_queue[$idx]->bids); // Go around again to make sure it doesn't tie again
									}
								}
								$this->job_queue[$idx]->bids->{$newmsg['sender']}=$v;
								break;
							// case 'deadline':
							// 	$this->job_queue[$idx]->$k=$v;
							// 	break;
							default:
								break;
						}
					}
					// print "Job updated:";print_r($this->job_queue[$idx]);print "\n";
				}
			}
		}
	}
	
	public function create_job($job,$msg)
	{
		if (!is_string($job))
			$job=json_encode($job);
			
		$job_msg=new stdClass;
		$job_msg->msg=$msg;
		$job_msg->ctime=time();
		$job_msg->sha1key=sha1($job);
		$job_msg->job=$job;
		$job_msg->bids=new stdClass;

		// Make slots for bidders
		foreach ($this->job_group_members as $member) {
			$job_msg->bids->$member=null;
		}
		
		$this->update_job($job_msg);
	}

	private function is_our_msg_group($groups) {
		foreach ($this->msg_group as $g) {
			if (in_array($g,$groups)) {
				return true;
			}
		}
		return false;
	}

	private function bid_winner_is_me($job_queue_item) {
		if ($this->all_potential_bidders_have_bid($job_queue_item)===FALSE)
			return FALSE; // We can't determine yet
		
		// print_r($job_queue_item);
		foreach ($job_queue_item->bids as $host=>$bid) {
			// Compare all bids from other hosts in the group to my bid
			if (in_array($host,$this->job_group_members) && ($bid < $job_queue_item->bids->{$this->my_spread_name})) { 
				return FALSE; // No, you are not a winner
			}
		}

		return TRUE; // Yes, everyone expected to bid has bid and you have the lowest bid
	}

	private function print_queue() {
		print "Job Queue (".count($this->job_queue)."):\n";
		foreach ($this->job_queue as $job_queue_item) {
			print $job_queue_item->sha1key.':'.$job_queue_item->job.' '.count(get_object_vars($job_queue_item->bids)).' bidders (';
			foreach ($job_queue_item->bids as $m=>$b) {
				print $b.' ';
			}
			print ")\n";
		}
	}
	
	public function next_job($job_time_estimate=300) {

		// Find a job to do
		do {
			$this->message_loop(); // Process messages
			
			// $this->print_queue();

			foreach ($this->job_queue as &$job_queue_item) {
				//
				// Have I won any bids to do a job?
				//
				if ($this->bid_winner_is_me($job_queue_item)) {
					//
					// I won!
					//
					if (!$job_queue_item->completed) {
						// Note: I may be giving it out again to myself because job_done() was never called.
						$job=json_decode($job_queue_item->job);
						if (!is_null($job))
							$job_queue_item->job=$job;
						return $job_queue_item;
					}
				}
				else if (!$job_queue_item->ihavebid) {
					// I haven't bid, submit my bid
					$job_msg=array(
						'sha1key' => $job_queue_item->sha1key,
						'bid' => rand()
					);

					$this->update_job($job_msg);
				
					// Remind myself that I've submitted a bid, so I don't need to keep doing it
					$job_queue_item->ihavebid=TRUE;
				}
				else {
					// Either I lost or we are still waiting on bids from others and we can't do anything with it.
					// TODO: put old jobs up for bid again
				}
			}
		}
		while ($this->job_count());
		
		return FALSE; // No job for us to do
	}
	
	public function set_message_callback($callback) {
		$this->message_callback=$callback;
	}
	
	public function gimme_jobs($callback,$signal_handler=null) {

		if (!is_array($callback))
			$callback=array($callback);
			
		if (is_null($signal_handler))
			$signal_handler=array($this,'gimme_jobs_default_sig_handler');
			
		// Setup signal handler to stop this loop gracefully
		foreach (array(SIGUSR1,SIGUSR2,SIGTERM,SIGINT,SIGABRT,SIGCONT) as $sig) {
			pcntl_signal($sig,$signal_handler);
		}
		
		$this->gimme_jobs_continue=TRUE;
		do {
			pcntl_signal_dispatch(); // Process any pending signals
			
			$job=$this->next_job();
				
			if ($job !== FALSE) {
				// Figure out which callback to call
				for ($i=0,$j=count($this->msg_group);$i < $j;++$i) {
					if (in_array($this->msg_group[$i],$job->msg->groups)) {
						break;
					}
				}
			
				$i=min($i,count($callback)-1); // Call the last function in the array if the array is smaller than count($this->msg_group)
			
				if ($i < count($callback) && call_user_func($callback[$i],$this,$job->job))
					$this->job_done($job->job);
				else
					$this->clear_my_bid($job->job); // Gives someone else a chance at it
			}
			else {
				usleep(250000); // Don't monopolize the CPU, sleep for 1/4th of a second
			}
		}
		while ($this->gimme_jobs_continue);
	}
	
	public function gimme_jobs_stop() {
		$this->gimme_jobs_continue=FALSE;
	}

	private function all_potential_bidders_have_bid($job_queue_item) {
		if (!isset($job_queue_item->bids))
			return FALSE;
			
		foreach ($job_queue_item->bids as $member=>$bid) {
			// If the member is in the current group and hasn't yet bid, we say No
			if (is_null($bid) && in_array($member,$this->job_group_members))
				return FALSE;
		}
		return TRUE;
	}
	
	private function update_job($job_msg) {
		$this->oak->broadcast_msg($this->job_group,$job_msg);
		$this->message_loop();
	}
	
	public function job_count() {
		$this->message_loop(); // Process messages
		return count($this->job_queue);
	}

	public function group_member_count() {
		$this->message_loop();
		return count($this->job_group_members);
	}
	
	private function clear_my_bid($job) {
		$idx=$this->find_job_in_queue($job);
		if ($idx!==FALSE)
			$this->job_queue[$idx]->ihavebid=FALSE;
	}
	
	public function job_done($job) {
		$idx=$this->find_job_in_queue($job);
		if ($idx!==FALSE) {
			$this->job_queue[$idx]->completed=TRUE;
			// Tell everyone I did it, they'll remove it from their queues
			$this->update_job($this->job_queue[$idx]);
		}
	}
	
	private function find_job_in_queue($job) {
		if (is_scalar($job))
			$sha1key=sha1($job);
		else
			$sha1key=sha1(json_encode($job));

		// Find the job in the queue
		for ($i=0,$j=count($this->job_queue);$i<$j;++$i) {
			if ($this->job_queue[$i]->sha1key==$sha1key) {
				return $i;
			}
		}
		return FALSE;
	}

	private function gimme_jobs_default_sig_handler($signo)
	{
		switch ($signo)
		{
		case SIGTERM: // 15
		case SIGINT:  // 2
		case SIGQUIT: // 3       /* Quit (POSIX).  */
		case SIGABRT: // 6       /* Abort (ANSI).  */
		case SIGKILL: // 9       /* Kill, unblockable (POSIX).  */
		case SIGHUP:  // 1
		case SIGSTOP: // 19      /* Stop, unblockable (POSIX).  */
		case SIGTSTP: // 20      /* Keyboard stop (POSIX).  */
			$this->gimme_jobs_stop();
			break;
		case SIGUSR1: // 10      /* User-defined signal 1 (POSIX).  */
		case SIGUSR2: // 12      /* User-defined signal 2 (POSIX).  */
		case SIGCONT: // 18      /* Continue (POSIX).  */
			break;
		default:
			break;
		}
	}
};


?>
