<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class IndexController extends Controller
{
	public function indexAction()
	{
    header("HTTP/1.0 404 Not Found");
    echo "Page not found";
    exit();
	}

}
