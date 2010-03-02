<?php
require_once 'OAK/oak.class.php';

class Beer extends OAKDocument
{
	static function createBeer($brewery_id,$beer_name)
	{
		if (empty($brewery_id)) // Can't do this, must have a valid brewery_id
			throw new Exception('Must have a valid brewery_id');
		if (empty($beer_name)) // Can't do this, must have a valid name
			throw new Exception('Must have a name');
		
		$beer=new Beer;
		// Create an ID based on brewery_id and name
		// Strip off the 'brewery:' part of the brewery_id
		if (substr($brewery_id,0,8)==='brewery:')
			$brewery_id=substr($brewery_id,8);
		$beer->setID('beer:'.$brewery_id.':'.preg_replace('/[^a-zA-Z0-9]+/','-',$beer_name));
		// TODO: Verify that there is not an existing beer with this ID
		return $beer;
	}
	
	static function validateID($name,$value,$attribs,$converted_value)
	{
		// TODO: really validate the Beer ID
		return true;
	}

	
	function __construct()
	{
		parent::__construct('beer');
	}
	
	function __set($name,$val)
	{
		switch ($name)
		{
		case "calories_per_ml":
		case "abv":
		case "ibu":
		case "og":
		case "fg":
		case "srm":
		case "brewery_id":
			$attribs="@attributes";
			if (!isset($this->$attribs))
				$this->$attribs=new stdClass;
			$this->$attribs->$name=$val;
			break;
		case "availability":
		case "description":
		case "grains":
		case "hops":
		case "ingredients":
		case "name":
		case "otherings":
		case "yeast":
		case "sizes":
		case "styles":
			$this->$name=$val;
			break;
		default:
			return parent::__set($name,$val);
			break;
		}
	}
	
	function __get($name)
	{
		switch ($name)
		{
		case "calories_per_ml":
		case "abv":
		case "ibu":
		case "brewery_id":
			$attribs="@attributes";
			return $this->$attribs->$name;
			break;
		default:
			return parent::__get($name);
			break;
		}

		throw new Exception('Internal error');
	}
};

?>