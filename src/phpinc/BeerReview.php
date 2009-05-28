<?php

function validateBeerID($name,$value,$attribs,$converted_value)
{
	// TODO: really validate the Beer ID
	return true;
}


class BeerReview
{
	function __construct() 
	{
		$this->type="beer_review";
		$this->timestamp=time();
	}

	function __destruct() 
	{
	}
	
	function __set($name,$value)
	{
		$this->$name=$value;
	}
	
	function getID()
	{
		if (!isset($this->user_id))
			throw new Exception("user_id not set");
		if (!isset($this->beer_id))
			throw new Exception("beer_id not set");
		if (!isset($this->type))
			throw new Exception("type not set");
		return $this->type.':'.$this->beer_id.':'.$this->user_id;
	}
};

?>
