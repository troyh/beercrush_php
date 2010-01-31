<?php
require_once('beercrush/oak.class.php');

$oak=new OAK;
$beers=new stdClass;
$doc=array();

switch ($_GET['view'])
{
	case 'date':
		$oak->get_view('beer/timestamp?descending=true',$beers);
		// var_dump($beers);exit;
		foreach ($beers->rows as $row)
		{
			$parts=preg_split('/:/',$row->id);
			$doc['days'][date('Y',$row->key)][date('m',$row->key)][date('d',$row->key)][]=array(
				"id" => $row->id,
				"name" => $row->value,
				"brewery_id" => 'brewery:'.$parts[1],
			);
		}
		break;
	case 'name':
	default:
		$oak->get_view('beer/all',$beers);
		foreach ($beers->rows as $row)
		{
			$letter=strtoupper(substr($row->key,0,1));
			if (!ctype_alpha($letter))
				$letter='#';

			$parts=preg_split('/:/',$row->id);
	
			$doc[$letter][]=array(
				"id" => $row->id,
				"name" => $row->key,
				"brewery_id" => 'brewery:'.$parts[1],
			);
		}
		break;
}
	
// var_dump($beers);exit;
header('Content-Type: application/json; charset=utf-8');
print json_encode($doc);

?>
