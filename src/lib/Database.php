<?php

namespace App\Lib;

require __DIR__.'/../../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;
use Exception;

class Database {
  private $connection = null;

  public function __construct() {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();

    $dbParams = [
      'dbname'   => $_ENV['DB_NAME'] ?? throw new Exception('DB_NAME is missing'),
      'user'     => $_ENV['DB_USER'] ?? throw new Exception('DB_USER is missing'),
      'password' => $_ENV['DB_PASS'] ?? throw new Exception('DB_PASS is missing'),
      'host'     => $_ENV['DB_HOST'] ?? throw new Exception('DB_HOST is missing'),
      'port'     => $_ENV['DB_PORT'] ?? throw new Exception('DB_PORT is missing'),
      'driver'   => 'pdo_' . ($_ENV['DB_CLIENT'] ?? 'mysql'),
    ];

    $this->connection = DriverManager::getConnection($dbParams);
  }

  public function getConnection() {
    return $this->connection;
  }
}