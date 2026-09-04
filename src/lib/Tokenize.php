<?php

namespace App\Lib;


use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Tokenize
{
  private $key;

  function __construct(array $config)
  {
    $this->key = $config['secret_key'];
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