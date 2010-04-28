#!/bin/bash

php -r '
$cfg=json_decode(file_get_contents("/etc/BeerCrush/webapp.conf"));
if (!is_null($cfg)) 
	print $cfg->webservices->S3->accesskey." ".$cfg->webservices->S3->secretkey." ".$cfg->webservices->S3->photobucket."\n";
' | if read S3_ACCESSKEY S3_SECRETKEY S3_PHOTOBUCKET; then

	##################################################
	# Verify config settings exist
	##################################################
	if [ -z "$S3_ACCESSKEY" ]; then
		echo "ERROR: webservices.S3.accesskey is not set";
		exit 1;
	fi

	if [ -z "$S3_SECRETKEY" ]; then
		echo "ERROR: webservices.S3.secretkey is not set";
		exit 1;
	fi

	if [ -z "$S3_PHOTOBUCKET" ]; then
		echo "ERROR: webservices.S3.photobucket is not set";
		exit 1;
	fi
	
	if [[ ! $S3_ACCESSKEY =~ ^[A-Z0-9]+$ ]]; then
		echo "ERROR: $S3_ACCESSKEY is not a valid S3 Access Key";
		exit 1;
	fi

	if [[ ! $S3_SECRETKEY =~ ^[a-zA-Z0-9]+$ ]]; then
		echo "ERROR: $S3_SECRETKEY is not a valid S3 Secret Key";
		exit 1;
	fi

	if [[ ! $S3_PHOTOBUCKET =~ ^[a-z0-9-]+$ ]]; then
		echo "ERROR: $S3_PHOTOBUCKET is not a valid S3 bucket name";
		exit 1;
	fi

	##################################################
	# Verify S3 Bucket exists
	##################################################
	if ! php -r '
require_once("OAK/S3/S3.php");
$s3=new S3($argv[1],$argv[2]);
if ($s3->getBucket($argv[3])===false) {
	exit(1);
}' $S3_ACCESSKEY $S3_SECRETKEY $S3_PHOTOBUCKET; then
		echo "ERROR: S3 bucket $S3_PHOTOBUCKET does not exist. Please create it.";
	fi

	
	
else
	echo "ERROR: Unable to read config";
	exit 1;
fi
