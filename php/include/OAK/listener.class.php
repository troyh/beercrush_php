<?php
require_once('OAK/oak.class.php');

class OAKListener {
	private $oak=null;
	private $msg_group=null;
	
	public function __construct($oak,$group) {
		$this->oak=$oak;
		$my_name='OAK-'.getmypid();
		$this->msg_group=$group;
		
		if ($this->oak && $this->oak->spread_connect(4803,$my_name,TRUE)!==TRUE) {
			$this->oak->log('Unable to connect to Spread daemon',OAK::LOGPRI_CRIT);
		}
		else if ($this->oak->spread_join($this->msg_group)!==TRUE) {
			$this->oak->log('Unable to join Spread group '.$this->msg_group,OAK::LOGPRI_CRIT);
		}
	}

	public function __destruct() {
		if ($this->oak) {
			$this->oak->spread_leave($this->msg_group);
			$this->oak->spread_disconnect();
		}
	}
	
	public function getOAK() { return $this->oak; }

	public function gimme_messages($callback,$signal_handler=null,$idle_callback=null,$idle_callback_interval=3600) {

		if (is_null($signal_handler))
			$signal_handler=array($this,'gimme_messages_default_sig_handler');
			
		// Setup signal handler to stop this loop gracefully
		foreach (array(SIGUSR1,SIGUSR2,SIGTERM,SIGINT,SIGABRT,SIGCONT) as $sig) {
			pcntl_signal($sig,$signal_handler);
		}
		
		$idle_callback_stopwatch=time()+$idle_callback_interval;
		
		$this->gimme_messages_continue=TRUE;
		do {
			$newmsg=$this->oak->spread_receive(null);
			if ($newmsg) {
				if (OAK::IS_MEMBERSHIP_MESS($newmsg['service_type'])) { // A Spread membership message
				}
				else {
					$msg=json_decode($newmsg['message']);
					if (is_null($msg))
						$msg=$newmsg['message'];
					call_user_func($callback,$this,$msg);
				}
			}
			else {
				pcntl_signal_dispatch();

				usleep(250000); // Don't monopolize the CPU, sleep for 1/4th of a second

				if (!is_null($idle_callback)) {
					if (time() > $idle_callback_stopwatch) {
						call_user_func($idle_callback,$this);
						$idle_callback_stopwatch=time()+$idle_callback_interval;
					}
				}
			}
		}
		while ($this->gimme_messages_continue);
	}
	
	public function gimme_messages_stop() {
		$this->gimme_messages_continue=FALSE;
	}

	private function gimme_messages_default_sig_handler($signo)
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
			$this->gimme_messages_stop();
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
