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
    $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
    $dotenv->safeLoad();

    try {
      $this->client = new Client([
        'scheme'     => $_ENV['VALKEY_SCHEME'] ?? 'tcp',
        'host'       => $_ENV['VALKEY_HOST'],
        'port'       => $_ENV['VALKEY_PORT'],
        'username'   => $_ENV['VALKEY_USERNAME'] ?? null,
        'password'   => $_ENV['VALKEY_PASSWORD'] ?? null,
        'persistent' => true,
        'ssl'        => ($_ENV['VALKEY_SCHEME'] === 'tls') ? ['verify_peer' => false] : null,
      ]);

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
   * Delete all keys matching a prefix pattern.
   *
   * This will safely scan Redis using non-blocking SCAN command
   * and delete all keys that start with given prefix.
   * 
   * @param string $prefix Prefix string (without wildcard)
   * @return bool True if success, False if failed or Redis not connected
   */
  public function deleteByPrefix($prefix)
  {
    if (!$this->isConnected) return false;

    $cursor = 0;
    $pattern = $prefix . '*';

    try {
      do {
        $result = $this->client->scan($cursor, ['match' => $pattern, 'count' => 100]);
        $cursor = $result[0];
        $keys = $result[1];

        if (!empty($keys)) {
          $this->client->del($keys);
        }
      } while ($cursor != 0);

      return true;
    } catch (Exception $e) {
      // Optional: log error here
      return false;
    }
  }

  /**
   * Delete All key
   *
   * @return boolean true/false
   */
  public function deleteAll()
  {
    if (!$this->isConnected) return false;

    try {
      $this->client->flushdb();
      return true;
    } catch (Exception $e) {
      return false;
    }
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
