<?php

namespace App\Lib;

use Dotenv\Dotenv;
use Predis\Client;
use Exception;

class Valkey
{
  protected $client;
  protected $isConnected = false;

  public function __construct($testDate = null)
  {
    $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
    $dotenv->safeLoad();

    $date = $testDate ? new \DateTime($testDate) : new \DateTime();
    $day = (int)$date->format('j');

    //* Determine credential prefix based on date range
    // if ($day >= 1 && $day <= 10) {
    //   $prefix = 'VALKEY1';
    // } elseif ($day >= 11 && $day <= 20) {
    //   $prefix = 'VALKEY2';
    // } else {
    //   $prefix = 'VALKEY3';
    // }
    // $getEnv = function($key) use ($prefix) {
    //   return $_ENV["{$prefix}_{$key}"] ?? $_ENV["VALKEY_{$key}"] ?? null;
    // };

    $getEnv = function($key) {
      return $_ENV["VALKEY_{$key}"] ?? null;
    };

    try {
      $scheme = $getEnv('SCHEME') ?? 'tls';
      $this->client = new Client([
        'scheme'             => $scheme,
        'host'               => $getEnv('HOST'),
        'port'               => $getEnv('PORT'),
        'username'           => $getEnv('USERNAME'),
        'password'           => $getEnv('PASSWORD'),
        'persistent'         => true,
        'timeout'            => 2.5, //! Max 2.5s untuk establish connection
        'read_write_timeout' => 2.5, //! Max 2.5s untuk read/write command
        'ssl'                => ($scheme === 'tls') ? ['verify_peer' => false] : null,
      ]);

      //* Predis menggunakan lazy connection, koneksi nyata terjadi saat command pertama dikirim.
      //* isConnected = true hanya menandakan client sudah terkonfigurasi.
      //* Setiap method memiliki try-catch sendiri untuk menangani kegagalan koneksi.
      $this->isConnected = true;

      //* Auto-flush logic on 1st and 16th
      // if ($day === 1 || $day === 16) {
      //   $flushLockKey = 'system:flush_lock:' . $date->format('Y-m-d');
      //   if (!$this->exists($flushLockKey)) {
      //     $this->deleteAll();
      //     $this->set($flushLockKey, 'flushed', 172800); //! Set lock for 48 hours to prevent multiple flushes
      //   }
      // }

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

    try {
      $this->client->set($key, $value);
      if ($expireIn) {
        $this->client->expire($key, $expireIn);
      }
      return true;
    } catch (Exception $e) {
      $this->isConnected = false;
      return false;
    }
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

    try {
      return $this->client->get($key);
    } catch (Exception $e) {
      $this->isConnected = false;
      return null;
    }
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

    try {
      return $this->client->del([$key]);
    } catch (Exception $e) {
      $this->isConnected = false;
      return false;
    }
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

  public function deleteByPrefixes(array $prefixes)
  {
    $result = true;

    foreach ($prefixes as $prefix) {
      $deleted = $this->deleteByPrefix($prefix);
      if ($deleted === false) {
        $result = false;
      }
    }

    return $result;
  }

  public function deleteListCaches(array $cacheKeys)
  {
    $prefixes = array_map(fn ($key) => "{$key}:list", $cacheKeys);
    return $this->deleteByPrefixes($prefixes);
  }

  public function getKeysByPrefix($prefix)
  {
    if (!$this->isConnected) return [];

    $cursor = 0;
    $pattern = $prefix . '*';
    $allKeys = [];

    try {
      do {
        $result = $this->client->scan($cursor, ['match' => $pattern, 'count' => 100]);
        $cursor = $result[0];
        $keys = $result[1];

        if (!empty($keys)) {
          $allKeys = array_merge($allKeys, $keys);
        }
      } while ($cursor != 0);

      return array_unique($allKeys);
    } catch (Exception $e) {
      return [];
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

    try {
      return $this->client->exists($key) > 0;
    } catch (Exception $e) {
      $this->isConnected = false;
      return false;
    }
  }

  /**
   * Push a value onto the head of a list (Queue).
   * * @param string $key
   * @param mixed $value
   * @return int|false The length of the list after the push, or false
   */
  public function lpush($key, $value)
  {
    if (!$this->isConnected) return false;

    try {
      return $this->client->lpush($key, [$value]);
    } catch (Exception $e) {
      $this->isConnected = false;
      return false;
    }
  }

  /**
   * Remove and return the last element of a list (Process Queue).
   * * @param string $key
   * @return string|null
   */
  public function rpop($key)
  {
    if (!$this->isConnected) return null;

    try {
      return $this->client->rpop($key);
    } catch (Exception $e) {
      $this->isConnected = false;
      return null;
    }
  }

  /**
   * Increment the integer value of a key by one.
   * * @param string $key
   * @return int|false The value of key after the increment, or false if not connected
   */
  public function increment($key)
  {
    if (!$this->isConnected) return false;

    try {
      return $this->client->incr($key);
    } catch (Exception $e) {
      $this->isConnected = false;
      return false;
    }
  }
}
