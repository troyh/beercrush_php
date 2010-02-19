<?php
header('Cache-Control: no-cache');

if  (empty($_POST['fname']) || empty($_POST['host'])) {
	header('HTTP/1.0 400 Missing fname/host');
	exit;
}

$filename='/var/local/BeerCrush/uploads/'.$_POST['fname'].'.info';

if (!file_exists($filename)) {
	// Fetch it from the host $_POST['host']
	$ssh=ssh2_connect($_POST['host']);
	if ($ssh===FALSE) {
		header('HTTP/1.0 404 Upload not found');
		exit;
	}
	else {
		$fingerprint=ssh2_fingerprint($ssh);
		if (ssh2_auth_password($ssh,"troy","voivod")==false) {
			header('HTTP/1.0 500 Access failure');
			exit;
		}
		else if (ssh2_exec($ssh,"rm -f ".$filename)===false) {
			header('HTTP/1.0 500 Delete failed');
			exit;
		}
		else {
			print $filename." deleted\n";
		}
	}
}
else if (unlink($filename)===false) {
	header('HTTP/1.0 500 Delete failed');
	exit;
}
else {
	print $filename." deleted locally\n";
}


?>