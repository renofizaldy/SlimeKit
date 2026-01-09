<?php

require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

//* INIT
  $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
  $dotenv->safeLoad();

  $displayErrorDetails = true;
  if ($_ENV['ENVIRONMENT'] === 'PRODUCTION') {
    error_reporting(0);
    $displayErrorDetails = false;
  }
  date_default_timezone_set("Asia/Jakarta");

  new App\Lib\Database();
//* INIT

//* DEFINE APP
  $app = AppFactory::create();
  // $app->setBasePath($_ENV['BASE_PATH']);
  $app->addRoutingMiddleware();
  $app->addBodyParsingMiddleware();
  $app->addErrorMiddleware($displayErrorDetails, true, true);
//* DEFINE APP

//* LOAD SEMUA ROUTE
  foreach (glob(__DIR__ . '/../routes/*.php') as $routeFile) {
    (require $routeFile)($app);
  }
//* LOAD SEMUA ROUTE

$app->run();