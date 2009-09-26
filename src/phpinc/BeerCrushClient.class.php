<?php

class BeerCrushClient
{
	public function __construct($url, $username, $password)
	{
		$this->base_url=$url;
		$this->username=$username;
		$this->password=$password;
	}
	
	public function login($username,$password)
	{
		$status_code=$this->sendRequest('/api/login',"GET",array("userid" => $username, "password" => $password),$credentials);
		return $status_code;
	}
	
	public function newBrewery($name,&$brewery_doc)
	{
		$status_code=$this->sendRequest(
			'/api/brewery/edit',
			"POST",
			array(
				"name" => $name,
			),
			$brewery_doc);
		
		return $status_code;
	}
	
	private function sendRequest($url,$method,$data,&$answer)
	{
		$login_attempts=0;
		
		$url=$this->base_url.$url; // Prefix with base API URL
		
		do
		{
			$bRetry=FALSE;

			$ch=curl_init($url);
			if ($ch===FALSE)
				return null;
			
			if ($method=="GET")
			{
				$args=array();
				foreach ($data as $k=>$v)
				{
					$args[]=$k.'='.urlencode($v);
				}
				$url.='?'.join('&',$args);
			}
			else if ($method=="POST")
			{
				curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
			}
		
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
			curl_setopt($ch,CURLOPT_COOKIEJAR,getenv('HOME')."/BeerCrush.cookies");
			curl_setopt($ch,CURLOPT_COOKIEFILE,getenv('HOME')."/BeerCrush.cookies");
		
			$output=curl_exec($ch);
			$status_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
			// print "OUTPUT:$output\n";
			// print "Status code:$status_code\n";
			if ($status_code==200)
			{
				$answer=json_decode($output);
			}
			else if ($status_code==420)
			{
				if ($login_attempts<1)
				{
					$login_attempts++;
					$status_code=$this->login($this->username,$this->password);
					if ($status_code==200)
					{
						$bRetry=TRUE;
					}
				}
			}
			else
				$answer=null;

			curl_close($ch);
		}
		while ($bRetry);
		
		return $status_code;
	}
	
};

?>