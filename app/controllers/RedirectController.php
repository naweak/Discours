<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class RedirectController extends Controller
{
	public function indexAction()
	{
    header("Location: ".$this->dispatcher->getParam("url"));
    exit();
	}

}
