<?php
require_once 'beercrush/oak.class.php';

$cgi_fields=array(
	"menu_id"				=> array(type=>OAK::DATATYPE_TEXT),
	"place_id"				=> array(type=>OAK::DATATYPE_TEXT),
	"add_item"				=> array(type=>OAK::DATATYPE_TEXT),
	"del_item"				=> array(type=>OAK::DATATYPE_TEXT),
);

function oakMain($oak)
{
	if ($oak->login_is_trusted()!==true) // If the user is not logged in or we can't trust the login
	{
		$oak->request_login();
	}
	else
	{
		global $cgi_fields;
		
		$menu=new OAKDocument('menu');

		if ($oak->cgi_value_exists('menu_id',$cgi_fields)) // Editing existing menu
		{
			$menu_id=$oak->get_cgi_value('menu_id',$cgi_fields);
			// Get existing menu, if there is one so that we can update just the parts added/changed in this request
			if ($oak->get_document($menu_id,&$menu)!==true)
				throw new Exception("No existing menu $menu_id");
		}
		else if ($oak->cgi_value_exists('place_id',$cgi_fields)!==true)
		{
			throw new Exception("place_id required to create a menu");
		}
		else  // Adding a new menu
		{
			$menu->setID('menu:'.$oak->get_cgi_value('place_id',$cgi_fields));
			
			// See if a menu for this place already exists
			if ($oak->get_document($menu->getID(),&$menu)===true)
			{
				throw new Exception($menu->getID()." already exists");
			}
		}

		// Make any changes requested
		$adds=array();
		if ($oak->cgi_value_exists('add_item',$cgi_fields))
		{
			$adds=preg_split('/\s+/',$oak->get_cgi_value('add_item',$cgi_fields));
		}

		$dels=array();
		if ($oak->cgi_value_exists('del_item',$cgi_fields))
		{
			$dels=preg_split('/\s+/',$oak->get_cgi_value('del_item',$cgi_fields));
		}

		if (count($adds) || count($dels))
		{
			if (!isset($menu->items))
				$menu->items=array();

			// Make the requested changes
			foreach ($dels as $id)
			{
				for ($i=0,$j=count($menu->items);$i<$j;++$i)
				{
					if ($menu->items[$i]['@attributes']['id']==$id)
						unset($menu->items[$i]);
				}
			}

			$attribs='@attributes';
			foreach ($adds as $id)
			{
				// Does it already exist?
				$item=null;
				for ($i=0,$j=count($menu->items);$i<$j;++$i)
				{
					if ($menu->items[$i]->$attribs->id==$id)
						$item=$i;
				}
				if (is_null($item))
					$item=count($menu->items); // Add to end

				$parts=split(':',$id);
				$menu->items[$item]->$attribs=array(
					'type' => $parts[0],
					'id'=>$id,
					'ontap'=>true,
					'inbottle'=>false,
					'oncask'=>false,
					'price'=>0
				);
			}
			
			// Store in db
			if ($oak->put_document($menu->getID(),$menu)!==true)
			{
				header("HTTP/1.0 500 Save failed");
			}
			else
			{
				$oak->log('Edited:'.$menu->getID());
			
				$xmlwriter=new XMLWriter;
				$xmlwriter->openMemory();
				$xmlwriter->startDocument();
			
				$oak->write_document($menu,$xmlwriter);

				$xmlwriter->endDocument();
				print $xmlwriter->outputMemory();
			}
		}
	}
}

require_once 'beercrush/oak.php';
?>