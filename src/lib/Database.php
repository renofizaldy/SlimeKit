<?php

namespace App\Lib;


use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;
use Exception;

class Database {

  public function __construct(array $config) {
    $capsule = new Capsule;
    $capsule->addConnection([
      'driver'    => $config['driver'] ?? 'mysql',
      'host'      => $config['host'],
      'port'      => $config['port'],
      'database'  => $config['database'],
      'username'  => $config['username'],
      'password'  => $config['password'],
      'charset'   => $config['charset'] ?? 'utf8',
      'collation' => $config['collation'] ?? 'utf8_unicode_ci',
      'prefix'    => '',
      'options'   => array_replace(
        [
          PDO::ATTR_EMULATE_PREPARES => true,
        ],
        $config['options'] ?? []
      ),
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
  }
}