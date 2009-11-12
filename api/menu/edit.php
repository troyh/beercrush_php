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
			throw new Exception("place_id required if menu_id is not specified");
		}
		else // Create or edit menu based on place_id
		{
			$place=new OAKDocument('');
			if ($oak->get_document($oak->get_cgi_value('place_id',$cgi_fields),&$place)!==true)
				throw new Exception($oak->get_cgi_value('place_id',$cgi_fields)." is  not an existing place");
				
			$menu->setID('menu:'.$place->getID());
			
			// See if a menu for this place already exists
			if ($oak->get_document($menu->getID(),&$menu)===true)
			{
				// Do nothing, we don't care if it already exists or not.
			}
		}
		
		if (!isset($menu->items))
			$menu->items=array();

		// Make any changes requested
		$adds=array();
		if ($oak->cgi_value_exists('add_item',$cgi_fields))
		{
			$adds=preg_split('/\s+/',trim($oak->get_cgi_value('add_item',$cgi_fields)),-1,PREG_SPLIT_NO_EMPTY);
		}

		$dels=array();
		if ($oak->cgi_value_exists('del_item',$cgi_fields))
		{
			$dels=preg_split('/\s+/',trim($oak->get_cgi_value('del_item',$cgi_fields)),-1,PREG_SPLIT_NO_EMPTY);
		}

		if (count($adds) || count($dels))
		{
			// Make the requested changes
			foreach ($dels as $id)
			{
				for ($i=0,$j=count($menu->items);$i<$j;++$i)
				{
					if ($menu->items[$i]->id==$id)
					{
						array_splice($menu->items,$i,1);
						break;
					}
				}
			}

			foreach ($adds as $id)
			{
				// Does it already exist?
				$item=null;
				for ($i=0,$j=count($menu->items);$i<$j;++$i)
				{
					if ($menu->items[$i]->id==$id)
						$item=$i;
				}
				if (is_null($item))
					$item=count($menu->items); // Add to end

				$parts=split(':',$id);

				$menu->items[$item]=new stdClass;
				$menu->items[$item]->type=$parts[0];
				$menu->items[$item]->id=$id;
				$menu->items[$item]->ontap=true;
				$menu->items[$item]->inbottle=false;
				$menu->items[$item]->oncask=false;
				$menu->items[$item]->price=0;
			}
			
			// Store in db
			if ($oak->put_document($menu->getID(),$menu)!==true)
			{
				header("HTTP/1.0 500 Save failed");
				exit;
			}

			$oak->log('Edited:'.$menu->getID());
		}

		/*
		This block should be identical to that in menu/view.php and kept in-sync!!!
		*/
		unset($menu->_id);
		unset($menu->_rev);
	
		foreach ($menu->items as &$item)
		{
			if (!empty($item->id))
			{
				$itemdoc=new OAKDocument('');
				if ($oak->get_document($item->id,$itemdoc)===true)
				{
					switch ($item->type)
					{
					case 'beer':
						$item->name=$itemdoc->name;

						$brewerydoc=new OAKDocument('');
						if ($oak->get_document($itemdoc->brewery_id,$brewerydoc)===true)
						{
							$item->brewery=array(
								'id' => $brewerydoc->getID(),
								'name' => $brewerydoc->name,
							);
						}
						break;
					default:
						// Support other items besides beers?
						break;
					}
				}
			}
		}
		
		header('Content-Type: application/json; charset=utf-8');
		print json_encode($menu);
	}
}

require_once 'beercrush/oak.php';
?>