<?php
require_once('OAK/oak.class.php');

$oak=new OAK;
$users=new stdClass;
$doc=array();

switch ($_GET['view'])
{
	case 'date':
		$oak->get_view('user/timestamp?descending=true',$users);
		// var_dump($beers);exit;
		foreach ($users->rows as $row)
		{
			$parts=preg_split('/:/',$row->id);
			$doc['days'][date('Y',$row->key)][date('m',$row->key)][date('d',$row->key)][]=array(
				"id" => $row->id,
				"name" => $row->value,
			);
		}
		break;
	case 'name':
	default:
		$oak->get_view('user/all',$users);
		foreach ($users->rows as $row)
		{
			$letter=strtoupper(substr($row->key,0,1));
			if (!ctype_alpha($letter))
				$letter='#';

			$parts=preg_split('/:/',$row->id);
	
			$doc[$letter][]=array(
				"id" => $row->id,
				"name" => $row->key,
			);
		}
		break;
}
	
// var_dump($users);exit;
header('Content-Type: application/json; charset=utf-8');
print json_encode($doc);

?>
