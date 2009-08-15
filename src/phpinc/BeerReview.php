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
		$review->setID('review:'.$review->beer_id.':'.$review->user_id);
		
		return $review;
	}
	
	function __construct() 
	{
		parent::__construct('review');
	}

	function __destruct() 
	{
	}
	
};

?>
