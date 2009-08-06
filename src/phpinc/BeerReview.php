<?php


class BeerReview extends OAKDocument
{
	static function createReview($beer_id,$user_id)
	{
		if (empty($beer_id))
			throw new Exception('beer_id is empty');
		if (empty($user_id))
			throw new Exception('user_id is empty');

		// TODO: verify that beer_id and user_id are valid IDs for existing documents
		$review=new BeerReview;
		
		if (empty($review->type))
			throw new Exception('type is not set');
		
		$review->beer_id=$beer_id;
		$review->user_id=$user_id;
		$review->setID('review:beer:'.$review->beer_id.':'.$review->user_id);
		
		return $review;
	}
	
	function __construct() 
	{
		parent::__construct('review');
	}

	function __destruct() 
	{
	}
	
	function __set($name,$val)
	{
		switch ($name)
		{
		case "beer_id":
		case "rating":
		case "srm":
		case "body":
		case "bitterness":
		case "sweetness":
		case "aftertaste":
		case "comments":
		case "price":
		case "place":
		case "size":
		case "drankwithfood":
		case "food_recommend":
			$this->$name=$val;
			break;
		default:
			return parent::__set($name,$val);
			break;
		}
		
	}
	
};

?>
