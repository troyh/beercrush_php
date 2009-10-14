<?php
require_once('beercrush/oak.class.php');

$cgi_fields=array(
	"add_item"			=> array(type=>OAK::DATATYPE_TEXT),
	"del_item"			=> array(type=>OAK::DATATYPE_TEXT),
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

		header("Content-Type: text/plain"); // Debug only
		$add_items=array();
		$del_items=array();

		if ($oak->cgi_value_exists('del_item',$cgi_fields))
			$del_items=preg_split('/\s+/',trim($oak->get_cgi_value('del_item',$cgi_fields)));
		if ($oak->cgi_value_exists('add_item',$cgi_fields))
			$add_items=preg_split('/\s+/',trim($oak->get_cgi_value('add_item',$cgi_fields)));
			
		// Remove any duplicates in each list
		$add_items=array_unique($add_items);
		$del_items=array_unique($del_items);
			
		// Take the intersection of these two, these items cancel each other out
		$common=array_intersect($add_items,$del_items);

		// Take the difference of the intersection in each list
		$add_items=array_diff($add_items,$common);
		$del_items=array_diff($del_items,$common);

		$wishlist=new OAKDocument('wishlist');
		$wishlist->setID('wishlist:'.$oak->get_user_id());
		
		if ($oak->get_document($wishlist->getID(),&$wishlist)===false)
		{
			// No problem, we'll create one
		}
		
		if (!isset($wishlist->items))
			$wishlist->items=array();
		
		// Get a list of all the item_ids in the existing wishlist
		$existing_item_ids=array();
		for ($i=0,$j=count($wishlist->items); $i<$j; ++$i)
		{
			$existing_item_ids[$wishlist->items[$i]->item_id]=$i;
		}

		// Create a list of array indices that need to be deleted			
		$todelete=array();
		foreach ($del_items as $item_id)
		{
			if (isset($existing_item_ids[$item_id]))
				$todelete[]=$existing_item_ids[$item_id];
		}
		// Sort (reverse) the $todelete array so we can delete them backwards (so 
		// that the index values are correct throughout the process)
		rsort($todelete,SORT_NUMERIC);

		// Delete them all
		foreach ($todelete as $idx)
		{
			array_splice($wishlist->items,$idx,1);
		}

		// Now that deletes are done, refresh the list of all the item_ids in the
		// existing wishlist. If we don't do this, then the foreach loop below adds
		// the meta item back into the array because $existing_item_ids still contains
		// the deleted items.
								
		$existing_item_ids=array();
		for ($i=0,$j=count($wishlist->items); $i<$j; ++$i)
		{
			$existing_item_ids[$wishlist->items[$i]->item_id]=$i;
		}
		
		// Add any that should be added
		foreach ($add_items as $item_id)
		{
			if (isset($existing_item_ids[$item_id]))
			{	// Already there, refresh the mtime timestamp
				$wishlist->items[$existing_item_ids[$item_id]]->meta->mtime=time();
			}
			else // Add it
			{
				$newitem=new OAKDocument('item');
				$newitem->item_id=$item_id;
				$wishlist->items[]=$newitem;
			}
		}
		// print json_encode($wishlist);exit;

		$oak->put_document($wishlist->getID(),$wishlist);

		$oak->write_document_json($wishlist);
	}
}

require_once('beercrush/oak.php');

?>