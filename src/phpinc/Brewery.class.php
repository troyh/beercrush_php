<?php

class BreweryDocument extends OAKDocument
{
	static function createBrewery($name)
	{
		$brewery=new BreweryDocument();
		$id=preg_replace('/[^a-zA-Z0-9]+/','-',$name);
		$id=preg_replace('/--+/','-',$id);
		$brewery->setID($id);
		return $brewery;
	}
	
	public function __construct()
	{
		parent::__construct('brewery');
	}
	
	public function __destruct()
	{
	}
	
};

?>