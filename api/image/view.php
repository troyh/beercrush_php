<?php
header('Cache-Control: no-cache');
require_once('beercrush/beercrush.php');

$oak=new OAK;

$filename='/var/local/BeerCrush/images/'.chunk_split(substr($_GET['fname'],0,8),2,'/').$_GET['fname'];

if (!strcasecmp($_SERVER['REQUEST_METHOD'],'DELETE')) {
	$failures=0;
	
	if (file_exists($filename)) {
		if (unlink($filename)===FALSE) {
			$failures++;
		}
	}

	foreach ($oak->get_config_info()->photos->sizes as $size=>$size_info) {
		$sizefname=preg_replace('/\.jpg$/','.'.$size.'.jpg',$filename);
		if (file_exists($sizefname)) {
			if (unlink($sizefname)===FALSE) {
				$failures++;
			}
		}
	}
	
	if ($failures) {
		header('HTTP/1.0 500 Delete failed');
	}
	
	exit;
}


if (!file_exists($filename)) {
	header('HTTP/1.0 404 Not found');
	exit;
}

if (!empty($_GET['size'])) {
	$newfile=$oak->make_image_size($filename,$_GET['size'],preg_replace('/\.jpg$/','.'.$_GET['size'].'.jpg',$filename));
	$filename=$newfile['filename'];
}

// Serve the file
header('Content-Type: image/jpeg');
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.filesize($filename));

readfile($filename,false);

?>