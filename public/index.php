<?php

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Di\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Mvc\View;

require_once dirname(__DIR__)."/app/config/config.php"; // load configuration
require_once dirname(__DIR__)."/app/library/library.php"; // load Discours functions (is it alwats needed or can be loaded later?)

\Phalcon\Mvc\Model::setup
(
  array
  (    
    'notNullValidations' => false
  )
);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Register an autoloader
$loader = new Loader();
$loader->registerDirs(
    array(
        APP_PATH . '/controllers/',
        APP_PATH . '/models/'
    )
)->register();

// Create a DI
$di = new FactoryDefault();

// Setting up the view component
$di['view'] = function() {
    $view = new View();
    $view->setViewsDir(APP_PATH . '/views/');
    return $view;
};

// Setup a base URI so that all generated URIs include the "tutorial" folder
$di['url'] = function() {
    $url = new UrlProvider();
    $url->setBaseUri('/');
    return $url;
};

// Set the database service
$di['db'] = function() {
    return new DbAdapter(array(
        "host"     => MYSQL_HOST,
        "username" => MYSQL_USERNAME,
        "password" => MYSQL_PASSWORD,
        "dbname"   => MYSQL_DATABASE,
        "options" => array(
            //PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".MYSQL_ENCODING
        )
    ));
};

if (USE_ROUTES)
{
  $di->set('router', function() use ($config) {
    $router = new \Phalcon\Mvc\Router(false);
    $router->removeExtraSlashes(false);
    include(ROUTES_FILE);
    return $router;
  });
}

//if(!defined("PHALCON_FROM_DISCOURS"))
//{
  // Handle the request
  try {
      $application = new Application($di);
      echo $application->handle()->getContent();
  } catch (Exception $e) {
       echo "Exception: ", $e->getMessage();
  }
//}