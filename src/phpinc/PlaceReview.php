<?php

function validatePlaceID($name,$value,$attribs,$converted_value)
{
	// TODO: really validate the Place ID
	return true;
}


class PlaceReview
{
	function __construct() 
	{
		$this->type="place_review";
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
		if (!isset($this->place_id))
			throw new Exception("place_id not set");
		if (!isset($this->type))
			throw new Exception("type not set");
		return $this->type.':'.$this->place_id.':'.$this->user_id;
	}
};

?>
