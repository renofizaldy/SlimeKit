<?php

namespace App\Lib;

require __DIR__.'/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

class Tokenize
{
  private $key;

  function __construct()
  {
    $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
    $dotenv->safeLoad();
    $this->key = $_ENV['SYM_KEY'];
  }

  public function encode($data=[]) {
    if (empty($data)) {
      return false;
    }
    $keepLogin  = isset($data['keep_login']) && $data['keep_login'] === true;
    $expiration = $keepLogin ? time() + (30 * 24 * 60 * 60) : time() + (24 * 60 * 60);
    $payload    = array_merge($data, ['exp' => $expiration]);
    return JWT::encode($payload, $this->key, 'HS256');
  }

  public function decode($data) {
    return JWT::decode($data, new Key($this->key, 'HS256'));
  }

  public function verify_time($token) {
    $decode = $this->decode($token);
    if ($decode->exp > time()) {
      return true;
    } return false;
  }
}