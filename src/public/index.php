<?php

require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use App\Middlewares\ErrorHandlerMiddleware;
use App\Middlewares\CorsMiddleware;

//* INIT
  $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
  $dotenv->safeLoad();

  $displayErrorDetails = true;
  if ($_ENV['ENVIRONMENT'] === 'PRODUCTION') {
    error_reporting(0);
    $displayErrorDetails = false;
  }
  date_default_timezone_set("Asia/Jakarta");
//* INIT

//* DI CONTAINER
  $containerBuilder = new \DI\ContainerBuilder();
  $dependencies = require __DIR__ . '/../config/dependencies.php';
  $dependencies($containerBuilder);
  $container = $containerBuilder->build();

  //* Bootstrap Database (Eloquent)
  $container->get(App\Lib\Database::class);
//* DI CONTAINER

//* DEFINE APP
  $app = AppFactory::create(container: $container);
  // $app->setBasePath($_ENV['BASE_PATH']);
  $app->addRoutingMiddleware();
  $app->addBodyParsingMiddleware();

  $app->add(new CorsMiddleware());

  // Ambil logger dari container jika tersedia
  $logger = $container->has(\Psr\Log\LoggerInterface::class) ? $container->get(\Psr\Log\LoggerInterface::class) : null;
  $app->add(new ErrorHandlerMiddleware($displayErrorDetails, $logger));
//* DEFINE APP

//* LOAD SEMUA ROUTE
  foreach (glob(__DIR__ . '/../routes/*.php') as $routeFile) {
    (require $routeFile)($app);
  }
//* LOAD SEMUA ROUTE

$app->run();