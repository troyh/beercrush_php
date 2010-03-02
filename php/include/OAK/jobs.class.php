<?php
require_once('oak.class.php');

class OAKJobs {
	private $oak=null;
	private $job_queue=array();
	private $job_group=null;
	private $job_group_members=array();
	private $my_spread_name=null;
	
	public function __construct($oak) {
		$this->oak=$oak;
	}
	public function __destruct() {
		if ($this->oak)
			$this->oak->spread_disconnect();
	}
	public function join_group($group=null) {
		if (is_null($group))
			$this->job_group='jobs-'.basename($_SERVER['PHP_SELF']);
		else
			$this->job_group='jobs-'.$group;
			
		$my_name='OAK-'.getmypid();
		
		if ($this->oak && $this->oak->spread_connect(4803,$my_name,TRUE)===TRUE) {
			if ($this->oak->spread_join($this->job_group)) {
				return TRUE;
			}
		}
		return FALSE;
	}
	public function leave_group() {
		if ($this->oak) {
			$this->oak->spread_leave($this->job_group);
		}
	}
	
	private function message_loop($timeout=null) {
		while (($newmsg=$this->oak->spread_receive($timeout))!=FALSE) {
			if (OAK::IS_MEMBERSHIP_MESS($newmsg['service_type'])) { // A Spread membership message
				// print "Received a membership message. Members:".join(',',$newmsg['group_members'])."\n";
				if ($newmsg['group']==$this->job_group) { // It's for our group
					$this->job_group_members=$newmsg['group_members'];
					$this->my_spread_name=$newmsg['group_members'][$newmsg['current_index']];
				}
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
					foreach ($job_queue_msg as $k=>$v) {
						// print "$k=";print_r($v)."\n";
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
				}
			}
		}
	}
	
	public function create_job($job)
	{
		if (!is_string($job))
			$job=json_encode($job);
			
		$job_msg=new stdClass;
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
		if (count($this->job_queue)==0) {
			print "Empty queue\n";
		}
		else {
			print "Job Queue (".count($this->job_queue)."):\n";
			foreach ($this->job_queue as $job_queue_item) {
				print $job_queue_item->sha1key.':'.$job_queue_item->job.' '.count(get_object_vars($job_queue_item->bids)).' bidders (';
				foreach ($job_queue_item->bids as $m=>$b) {
					print $b.' ';
				}
				print ")\n";
			}
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
					if (!$job_queue_item->assigned) {
						// We have to mark it locally as assigned or we may give it out again if next_job() is called 
						// before the Spread daemon can distribute the message to us.
						$job_queue_item->assigned=TRUE;
						return $job_queue_item->job;
					}
				}
				else if (!isset($job_queue_item->bids->{$this->my_spread_name})) {
					// I haven't bid, submit my bid
					$job_msg=array(
						'sha1key' => $job_queue_item->sha1key,
						'bid' => rand()
					);

					$this->update_job($job_msg);
				}
				else {
					// Either I lost or we are still waiting on bids from others and we can't do anything with it.
					// TODO: put old jobs up for bid again
				}
			}
		}
		while ($this->job_count());
		
		// No job for us to do
		return FALSE;
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
	
	public function job_refuse($job_msg) {
		if (is_scalar($job_msg))
			$sha1key=sha1($job_msg);
		else
			$sha1key=sha1(json_encode($job_msg));

		// Find the job in the queue
		foreach ($this->job_queue as $job_queue_item) {
			if ($job_queue_item->sha1key==$sha1key) {
				$job_msg=array(
					'sha1key' => $job_queue_item->sha1key,
					'bid' => PHP_INT_MAX,
				);

				$this->update_job($job_msg);
				break;
			}
		}
	}
	
	public function job_done($job_msg) {
		if (is_scalar($job_msg))
			$sha1key=sha1($job_msg);
		else
			$sha1key=sha1(json_encode($job_msg));

		// Find the job in the queue
		foreach ($this->job_queue as $job_queue_item) {
			if ($job_queue_item->sha1key==$sha1key) {
				$job_queue_item->completed=TRUE;
				$this->update_job($job_queue_item);
				break;
			}
		}
	}
};

?>