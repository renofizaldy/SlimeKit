<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

class TestCase extends PHPUnitTestCase
{
  /**
   * Membangun instance aplikasi Slim untuk testing
   * 
   * @return App
   */
  protected function getAppInstance(): App
  {
    // Load dotenv
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();

    // Setup Container (DI)
    $containerBuilder = new \DI\ContainerBuilder();
    $dependencies = require __DIR__ . '/../src/config/dependencies.php';
    $dependencies($containerBuilder);
    $container = $containerBuilder->build();

    // Bootstrap Database (Eloquent)
    $container->get(\App\Lib\Database::class);

    // Setup App
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    // Tambahkan Middlewares
    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware();
    $app->add(new \App\Middlewares\CorsMiddleware());

    $logger = $container->has(\Psr\Log\LoggerInterface::class) ? $container->get(\Psr\Log\LoggerInterface::class) : null;
    $app->add(new \App\Middlewares\ErrorHandlerMiddleware(true, $logger));

    // Load Routes
    foreach (glob(__DIR__ . '/../src/routes/*.php') as $routeFile) {
      (require $routeFile)($app);
    }

    return $app;
  }

  /**
   * Membuat dummy request (mensimulasikan request dari Postman/Frontend)
   *
   * @param string $method
   * @param string $path
   * @param array  $headers
   * @param array  $cookies
   * @param array  $serverParams
   * @return Request
   */
  protected function createRequest(
    string $method,
    string $path,
    array $headers = ['HTTP_ACCEPT' => 'application/json'],
    array $cookies = [],
    array $serverParams = []
  ): Request {
    $uri = new Uri('', 'localhost', 80, $path);
    $handle = fopen('php://temp', 'w+');
    $stream = (new StreamFactory())->createStreamFromResource($handle);

    $h = new Headers();
    foreach ($headers as $name => $value) {
      $h->addHeader($name, $value);
    }

    return new SlimRequest($method, $uri, $h, $cookies, $serverParams, $stream);
  }

  /**
   * Helper untuk memparsing response JSON agar lebih mudah di test
   */
  protected function parseJson($response)
  {
    $body = (string) $response->getBody();
    return json_decode($body, true);
  }
}
