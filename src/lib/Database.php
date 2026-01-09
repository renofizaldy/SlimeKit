<?php

namespace App\Lib;

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;
use Exception;

class Database {

  public function __construct() {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();

    $capsule = new Capsule;
    $capsule->addConnection([
      'driver'    => $_ENV['DB_CLIENT'] ?? 'mysql',
      'host'      => $_ENV['DB_HOST'],
      'port'      => $_ENV['DB_PORT'],
      'database'  => $_ENV['DB_NAME'],
      'username'  => $_ENV['DB_USER'],
      'password'  => $_ENV['DB_PASS'],
      'charset'   => 'utf8',
      'collation' => 'utf8_unicode_ci',
      'prefix'    => '',
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
  }
}