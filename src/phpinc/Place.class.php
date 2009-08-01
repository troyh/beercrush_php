<?php

class PlaceDocument extends OAKDocument
{
	static function createPlace($name)
	{
		$place=new PlaceDocument();
		$id=preg_replace('/[^a-zA-Z0-9]+/','-',$name);
		$id=preg_replace('/--+/','-',$id);
		$place->setID($id);
		return $place;
	}
	
	public function __construct()
	{
		parent::__construct('place');
	}
	
	public function __destruct()
	{
	}
	
};

?>