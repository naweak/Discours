<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class PageController extends Controller
{

	public function indexAction()
	{
		$request = new Request();
		
		$page = $request->get("page");
    
    if ($this->dispatcher->getParam("page"))
    {
      $page = $this->dispatcher->getParam("page");
    }
		
		if (!preg_match('/[A-Za-z0-9]+/', $page))
		{
			die("Page name should be alphanumeric!");
		}
		
		$page_filename      = APP_PATH."/pages/$page.php";
		$dashboard_filename = APP_PATH."/dashboard/$page.php";
		
		if (file_exists($page_filename))
		{
			require $page_filename;
			exit();
		}
		
		if (file_exists($dashboard_filename))
		{
			require $dashboard_filename;
			exit();
		}
		
		die("404 Not Found");
	}

}
