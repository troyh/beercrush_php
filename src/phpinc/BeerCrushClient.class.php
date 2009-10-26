<?php

class BeerCrushClient
{
	public $debug=false;
	
	public function __construct($url, $email=null, $password=null)
	{
		$this->base_url=$url;
		$this->email=$email;
		$this->password=$password;
	}
	
	public function login($email,$password)
	{
		$status_code=$this->sendRequest('/api/login',"POST",array("email" => $email, "password" => $password),$credentials);
		$this->credentials=$credentials;
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
	
	public function editBrewery($id,$doc,&$brewery_doc)
	{
		$data=array();
		$this->flatten_document($doc,$data);
		
		$data['brewery_id']=$id;
		// print_r($data);exit;
		
		$status_code=$this->sendRequest(
			'/api/brewery/edit',
			"POST",
			$data,
			$brewery_doc);
		
		return $status_code;
	}
	
	public function newPlace($doc,&$place_doc)
	{
		$data=array();
		$this->flatten_document($doc,$data);

		$status_code=$this->sendRequest(
			'/api/place/edit',
			"POST",
			$data,
			$place_doc);
			
		return $status_code;
	}
	
	public function newBeer($beer_doc,&$new_beer_doc)
	{
		return $this->editBeer(null,$beer_doc,$new_beer_doc);
	}
	
	public function editBeer($beer_id,$beer_doc,&$new_beer_doc)
	{
		$data=array();
		$this->flatten_document($beer_doc,$data);
		
		if (!is_null($beer_id))
			$beer->beer_id=$beer_id;

		$status_code=$this->sendRequest(
			'/api/beer/edit',
			"POST",
			$data,
			$new_beer_doc);
		
		return $status_code;
	}
	
	public function getBeer($beer_id,&$beer_doc)
	{
		$status_code=$this->sendRequest(
			'/json/'.str_replace(':','/',$beer_id),
			"GET",
			null,
			$beer_doc);
			
		unset($beer_doc->_id);
		unset($beer_doc->_rev);
			
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
				throw new Exception('curl_init failed');

			// Always add userid & usrkey
			if (isset($this->credentials->userid) && isset($this->credentials->usrkey))
			{
				$data['userid']=$this->credentials->userid;
				$data['usrkey']=$this->credentials->usrkey;
			}

			if ($method=="GET")
			{
				if (!is_null($data))
				{
					$args=array();
					foreach ($data as $k=>$v)
					{
						$args[]=$k.'='.urlencode($v);
					}
					$url.='?'.join('&',$args);
				}
			}
			else if ($method=="POST")
			{
				curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
			}
			
			// print "POST data:";print_r($data);
		
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
			// curl_setopt($ch,CURLOPT_COOKIEJAR,getenv('HOME')."/BeerCrush.cookies");
			// curl_setopt($ch,CURLOPT_COOKIEFILE,getenv('HOME')."/BeerCrush.cookies");
			
			if ($this->debug)
				curl_setopt($ch,CURLOPT_VERBOSE,TRUE);
		
			$output=curl_exec($ch);
			$status_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);

			// print "OUTPUT:$output\n";
			
			if ($status_code==200)
			{
				$answer=json_decode($output);
			}
			else if ($status_code==403)
			{
				if ($login_attempts<1)
				{
					$login_attempts++;
					$login_status_code=$this->login($this->email,$this->password);
					if ($login_status_code==200)
					{
						$bRetry=TRUE;
					}
				}
			}
			else
				$answer=json_decode($output);

			curl_close($ch);
		}
		while ($bRetry);
		
		return $status_code;
	}

	private function flatten_document($doc,&$data,$prefix=null)
	{
		foreach ($doc as $k=>$v)
		{
			if (is_array($v) || is_object($v))
			{
				$this->flatten_document($v,$data,$k);
			}
			else if (is_scalar($v))
			{
				if (is_null($prefix))
					$data[$k]=$v;
				else
					$data["$prefix:$k"]=$v;
			}
			else
			{
				throw new Exception('Unsupported data type: '.gettype($v));
			}
		}
	}
	
};

?>