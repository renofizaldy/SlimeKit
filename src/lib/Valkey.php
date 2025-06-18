<?php

namespace App\Lib;

use Dotenv\Dotenv;
use Predis\Client;
use Exception;

class Valkey
{
  protected $client;
  protected $isConnected = false;

  public function __construct()
  {
    try {
      $this->client = new Client([
        'scheme'   => $_ENV['VALKEY_SCHEME'] ?? 'tcp', // tcp or tls (if SSL)
        'host'     => $_ENV['VALKEY_HOST'],
        'port'     => $_ENV['VALKEY_PORT'],
        'username' => $_ENV['VALKEY_USERNAME'] ?? null,
        'password' => $_ENV['VALKEY_PASSWORD'] ?? null,
        'ssl'      => ($_ENV['VALKEY_SCHEME'] === 'tls') ? ['verify_peer' => false] : null,
      ]);

      $this->client->connect();
      $this->client->ping();
      $this->isConnected = true;
    }
    catch (Exception $e) {
      $this->isConnected = false;
    }
  }

  /**
   * Check Redis connection
   * 
   * @return bool
   */
  public function isConnected()
  {
    return $this->isConnected;
  }

  /**
   * Set key with value.
   * 
   * @param string $key
   * @param mixed $value
   * @param int|null $expireIn (second)
   */
  public function set($key, $value, $expireIn = null)
  {
    if (!$this->isConnected) return false;

    $this->client->set($key, $value);
    if ($expireIn) {
      $this->client->expire($key, $expireIn);
    }
    return true;
  }

  /**
   * Get value from key.
   * 
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    if (!$this->isConnected) return null;
    return $this->client->get($key);
  }

  /**
   * Delete key
   *
   * @param string $key
   * @return int jumlah key yang dihapus
   */
  public function delete($key)
  {
    if (!$this->isConnected) return false;
    return $this->client->del([$key]);
  }

  /**
   * Check if key exists
   * 
   * @param string $key
   * @return bool
   */
  public function exists($key)
  {
    if (!$this->isConnected) return false;
    return $this->client->exists($key) > 0;
  }
}
