<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return [
  'paths' => [
    'migrations' => 'src/migrations',
    'seeds'      => 'src/seeds'
  ],
  'environments' => [
    'default_migration_table' => 'phinxlog',
    'default_environment'     => 'production',
    'production' => [
      'adapter' => $_ENV['DB_CLIENT'] ?? 'mysql',
      'host'    => $_ENV['DB_HOST'],
      'name'    => $_ENV['DB_NAME'],
      'user'    => $_ENV['DB_USER'],
      'pass'    => $_ENV['DB_PASS'],
      'port'    => $_ENV['DB_PORT'],
      'charset' => 'utf8',
    ],
  ],
  'version_order' => 'creation'
];
