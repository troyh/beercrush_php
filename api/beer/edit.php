<?php
require_once 'beercrush/oak.class.php';

function validate_brewery_id($name,$value,$attribs,$converted_value,$oak)
{
	// The brewery_id must already exist
	$brewery_doc=new stdClass;
	// If there's a doc for it, it's valid
	return $oak->get_document($converted_value,$brewery_doc);
}

function valid_srm_value($name,$value,$attribs,$converted_value,$oak)
{
	return (in_array($converted_value,array(2,3,4,6,9,12,15,18,21,24,30,40)));
}

function validate_beer_bjcp_style_id($name,$value,$attribs,$converted_value,$oak)
{
	// TODO: validate it for real against the true list of styles
	$styles=preg_split('/s+/',$converted_value);
	foreach ($styles as $style)
	{
	 	if (preg_match('/\d{1,2}[A-F]/',$style) == FALSE)
			return FALSE;
	}
	return TRUE;
}

function get_specific_gravity($n)
{
	if ($n < 0) // Bad value
		return null;

	if ((1.0 <= $n) && ($n < 1.1)) // It's a Specific Gravity value
	{
		// No change necessary
		return $n;
	}
	else if (1000 < $n) // It's a Specific Gravity value, but without the decimal point (this is common)
	{
		return $n/1000; // Correct the casual form to the correct form
	}
	else if ($n < 100) // It's a Degrees Plato value
	{
		return ($n/(258.6-($n/258.2)*227.1))+1; // From http://plato.montanahomebrewers.org/
	}
	
	// Bad value
	return null;
}

$cgi_fields=array(
	"abv"						=> array(type=>OAK::DATATYPE_FLOAT, min=>0.0, max=>100.0),
	"availability"				=> array(type=>OAK::DATATYPE_TEXT),
	"beer_id"					=> array(type=>OAK::DATATYPE_TEXT,flags=>OAK::FIELDFLAG_CGIONLY,minlen=>1),
	"styles"					=> array(type=>OAK::DATATYPE_TEXT,flags=>OAK::FIELDFLAG_CGIONLY, validatefunc=>validate_beer_bjcp_style_id),
	"style_text"				=> array(type=>OAK::DATATYPE_TEXT),
	"brewery_id"				=> array(type=>OAK::DATATYPE_TEXT, validatefunc=>validate_brewery_id ),
	"calories_per_ml"			=> array(type=>OAK::DATATYPE_INT, min=>0, max=>1000),
	"srm"						=> array(type=>OAK::DATATYPE_INT, validatefunc=>validate_srm_value),
	"description"				=> array(type=>OAK::DATATYPE_TEXT),
	"fg"						=> array(type=>OAK::DATATYPE_FLOAT),
	"grains"					=> array(type=>OAK::DATATYPE_TEXT),
	"hops"						=> array(type=>OAK::DATATYPE_TEXT),
	"ibu"						=> array(type=>OAK::DATATYPE_INT, min=>0, max=>1000),
	"ingredients"				=> array(type=>OAK::DATATYPE_TEXT),
	"name"						=> array(type=>OAK::DATATYPE_TEXT , minlen=>1, maxlen=>200),
	"og"						=> array(type=>OAK::DATATYPE_FLOAT),
	"otherings"					=> array(type=>OAK::DATATYPE_TEXT),
	"yeast"						=> array(type=>OAK::DATATYPE_TEXT),
);

require_once 'beercrush/Beer.class.php';


function oakMain($oak)
{
	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		$oak->request_login();
	}
	else
	{
		global $cgi_fields;
		
		$beer=new Beer;

		if ($oak->cgi_value_exists('beer_id',$cgi_fields)) // Editing existing beer
		{
			$beer_id=$oak->get_cgi_value('beer_id',$cgi_fields);
			// Get existing beer, if there is one so that we can update just the parts added/changed in this request
			if ($oak->get_document($beer_id,&$beer)!==true)
				throw new Exception("No existing beer $beer_id");
		}
		else  // Adding a new beer
		{
			$beer=Beer::createBeer($oak->get_cgi_value('brewery_id',$cgi_fields),$oak->get_cgi_value('name',$cgi_fields));
			
			// See if this beer already exists
			if ($oak->get_document($beer->getID(),&$beer)===true)
			{
				throw new Exception($beer->getID(),450);
			}
		}

		/* 
		Calculate OG and FG, which are kinda fuzzy. We store Specific Gravity, so we have to convert from Degrees Plato, if necessary
		*/
		if ($oak->cgi_value_exists('og',$cgi_fields))
		{
			$og=get_specific_gravity($oak->get_cgi_value('og',$cgi_fields));
			if (is_null($og))
				throw new Exception("OG value (".$oak->get_cgi_value('og',$cgi_fields).") is invalid");
			$oak->set_cgi_value('og',$cgi_fields,$og);
		}

		if ($oak->cgi_value_exists('fg',$cgi_fields))
		{
			$fg=get_specific_gravity($oak->get_cgi_value('fg',$cgi_fields));
			if (is_null($fg))
				throw new Exception("FG value (".$oak->get_cgi_value('fg',$cgi_fields).") is invalid");
			$oak->set_cgi_value('fg',$cgi_fields,$fg);
		}
		
		// Give it this request's edits
		$oak->assign_cgi_values(&$beer,$cgi_fields);
		
		$beer->name			=trim(preg_replace('/\s\s+/',' ',$beer->name));
		$beer->description	=trim(preg_replace('/\s\s+/',' ',$beer->description));
		$beer->grains		=trim(preg_replace('/\s\s+/',' ',$beer->grains));
		$beer->yeast		=trim(preg_replace('/\s\s+/',' ',$beer->yeast));
		
		// Set styles too
		if ($oak->cgi_value_exists('styles',$cgi_fields))
		{
			// Could be more than one...
			$beer->styles=preg_split('/\s+/',$oak->get_cgi_value('styles',$cgi_fields));
		}
	
		// Store in db
		if ($oak->put_document($beer->getID(),$beer)!==true)
		{
			header("HTTP/1.0 502 Save failed");
		}
		else
		{
			print json_encode($beer);
			$oak->log('Edited:'.$beer->getID());
		}
	}
}

require_once 'beercrush/oak.php';

?>