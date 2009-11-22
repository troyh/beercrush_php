<?php
require_once('beercrush/oak.class.php');
$oak=new OAK;

function accounting()
{
	// TODO: log a request by this user ($_GET['userid'] | $_POST['userid']) for this resource ($_SERVER['PATH_INFO'])
}

accounting();

switch ($_SERVER['PATH_INFO'])
{
	case "/beer/view":
	case "/beercolors":
	case "/beers":
	case "/beerstyles":
	case "/breweries":
	case "/brewery/beerlist":
	case "/brewery/edit":
	case "/brewery/view":
	case "/flavors":
	case "/login":
	case "/logout":
	case "/menu/view":
	case "/place/view":
	case "/places":
	case "/restaurantcategories":
	case "/review/beer":
	case "/search":
		header('X-Accel-Redirect: /store/api'.$_SERVER['PATH_INFO'].(empty($_SERVER['QUERY_STRING'])?'':'?').$_SERVER['QUERY_STRING']);
		break;
	case "/wishlist/view":
		if (substr($_GET['user_id'],0,5)==='user:')
			$user_id=substr($_GET['user_id'],5);
		else
			$user_id=$_GET['user_id'];

		if ($oak->login_is_trusted()!==TRUE)
		{
			$oak->request_login();
			exit;
		}

		// Verify that the requesting user has access permissions to this wishlist
		$requester_user_id=$oak->get_user_id();
		if (is_null($requester_user_id) || $requester_user_id!==$user_id)
		{
			header('HTTP/1.0 403 Permission denied');
			print "Permission denied";
			exit;
		}

		header('X-Accel-Redirect: /store/api/wishlist/view?user_id='.$user_id);
		break;
	default: // Default to a 403
		header('HTTP/1.0 403 Permission Denied');
		break;
}


?>