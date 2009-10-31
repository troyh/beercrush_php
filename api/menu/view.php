<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$menu=new stdClass;
if ($oak->get_document('menu:'.$_GET['place_id'],$menu)!==true)
{
	header('HTTP/1.0 400 No menu');
	exit;
}
else
{
	unset($menu->_id);
	unset($menu->_rev);
	
	// Add in basic beer & brewery info to the list
	foreach ($menu->items as &$item)
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
	header('Content-Type: text/javascript; charset=utf-8');
	print json_encode($menu);
}

?>