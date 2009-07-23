<?php
require_once 'beercrush/oak.class.php';

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
		$beer->setID($brewery_id.':'.preg_replace('/[^a-zA-Z0-9]+/','-',$beer_name));
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
		case "brewery_id":
			$attribs="@attributes";
			if (!isset($this->$attribs))
				$this->$attribs=new stdClass;
			$this->$attribs->$name=$val;
			break;
		case "sizes":
		case "styles":
			throw new Exception("NYI: setting $name");
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