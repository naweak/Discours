<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class DashboardController extends Controller
{

	public function indexAction()
	{
		// code copied from PageController.php, should be inside a function
		
		$request = new Request();
		
		$page = $request->get("page");
		
		if (!preg_match('/[A-Za-z0-9]+/', $page))
		{
			die("Page name should be alphanumeric!");
		}
		
		$filename = APP_PATH."/dashboard/$page.php";
		
		if (!file_exists($filename))
		{
			die("404 Not Found");
		}
		
		require $filename;
	}

}
