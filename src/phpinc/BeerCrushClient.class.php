<?php

class BeerCrushClient
{
	public $debug=false;
	
	public function __construct($url, $username, $password)
	{
		$this->base_url=$url;
		$this->username=$username;
		$this->password=$password;
	}
	
	public function login($username,$password)
	{
		$status_code=$this->sendRequest('/api/login',"POST",array("userid" => $username, "password" => $password),$credentials);
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
	
	public function addBeer($beer_doc,&$new_beer_doc)
	{
		return $this->editBeer(null,$beer_doc,$new_beer_doc);
	}
	
	public function editBeer($beer_id,$beer_doc,&$new_beer_doc)
	{
		$fields=array(
			"abv"				=> 'ABV',
			"availability"		=> 'availability',
			"brewery_id"		=> 'brewery_id',
			"calories_per_ml"	=> 'calories_per_ml',
			"description"		=> 'description',
			"fg"				=> 'FG',
			"grains"			=> 'grains',
			"hops"				=> 'hops',
			"ibu"				=> 'IBU',
			"ingredients"		=> 'ingredients',
			"name"				=> 'name',
			"og"				=> 'OG',
			"otherings"			=> 'otherings',
			"srm"				=> 'color',
			"style_text"		=> 'style_text',
			"yeast"				=> 'yeast',
		);

		$data=array();
		foreach ($fields as $k=>$f)
		{
			if (!is_null($beer_doc->$f))
				$data[$k]=$beer_doc->$f;
		}
		
		if (!is_null($beer_id))
			$data['beer_id']=$beer_id;

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
		
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
			curl_setopt($ch,CURLOPT_COOKIEJAR,getenv('HOME')."/BeerCrush.cookies");
			curl_setopt($ch,CURLOPT_COOKIEFILE,getenv('HOME')."/BeerCrush.cookies");
			
			if ($this->debug)
				curl_setopt($ch,CURLOPT_VERBOSE,TRUE);
		
			$output=curl_exec($ch);
			$status_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);

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
				$answer=json_decode($output);

			curl_close($ch);
		}
		while ($bRetry);
		
		return $status_code;
	}
	
};

?>