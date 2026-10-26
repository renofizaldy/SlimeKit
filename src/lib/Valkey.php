<?php

namespace App\Lib;


use Predis\Client;
use Exception;

class Valkey
{
  public const DOWN_COOLDOWN = 30; // seconds

  protected $client;
  protected $isConnected = false;
  protected $circuitBreakerFile;

  public function __construct(array $config, $testDate = null, ?string $circuitBreakerFile = null)
  {
    $this->circuitBreakerFile = $circuitBreakerFile ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'valkey_down.marker';

    if ($this->isCircuitBreakerOpen()) {
      $this->isConnected = false;
      return;
    }

    $date = $testDate ? new \DateTime($testDate) : new \DateTime();
    $day = (int)$date->format('j');

    try {
      $scheme = $config['scheme'] ?? 'tls';
      $this->client = new Client([
        'scheme'             => $scheme,
        'host'               => $config['host'],
        'port'               => $config['port'],
        'username'           => $config['username'],
        'password'            => $config['password'],
        'persistent'         => true,
        'timeout'            => 2.5, //! Max 2.5s untuk establish connection
        'read_write_timeout' => 2.5, //! Max 2.5s untuk read/write command
        'ssl'                => ($scheme === 'tls') ? ['verify_peer' => false] : null,
      ]);

      //* Predis menggunakan lazy connection, koneksi nyata terjadi saat command pertama dikirim.
      //* isConnected = true hanya menandakan client sudah terkonfigurasi.
      //* Setiap method memiliki try-catch sendiri untuk menangani kegagalan koneksi.
      $this->isConnected = true;

    }
    catch (Exception $e) {
      $this->markDown($e);
    }
  }

  /**
   * Check if circuit breaker is open (Valkey is currently in cooldown period)
   * 
   * @return bool
   */
  public function isCircuitBreakerOpen(): bool
  {
    if ($this->circuitBreakerFile && file_exists($this->circuitBreakerFile)) {
      $lastDownTime = @filemtime($this->circuitBreakerFile);
      if ($lastDownTime !== false && (time() - $lastDownTime) < self::DOWN_COOLDOWN) {
        return true;
      }
    }
    return false;
  }

  /**
   * Mark Valkey as down and record timestamp in marker file
   * 
   * @param Exception|null $e
   * @return void
   */
  public function markDown(?Exception $e = null): void
  {
    $this->isConnected = false;
    if ($this->circuitBreakerFile) {
      @touch($this->circuitBreakerFile);
    }
  }

  /**
   * Mark Valkey as up and remove marker file
   * 
   * @return void
   */
  public function markUp(): void
  {
    $this->isConnected = true;
    if ($this->circuitBreakerFile && file_exists($this->circuitBreakerFile)) {
      @unlink($this->circuitBreakerFile);
    }
  }

  public function getCircuitBreakerFile(): ?string
  {
    return $this->circuitBreakerFile;
  }

  public function setCircuitBreakerFile(string $path): void
  {
    $this->circuitBreakerFile = $path;
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
      $this->markUp();
      return true;
    } catch (Exception $e) {
      $this->markDown($e);
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
      $result = $this->client->get($key);
      $this->markUp();
      return $result;
    } catch (Exception $e) {
      $this->markDown($e);
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
      $result = $this->client->del([$key]);
      $this->markUp();
      return $result;
    } catch (Exception $e) {
      $this->markDown($e);
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

      $this->markUp();
      return true;
    } catch (Exception $e) {
      $this->markDown($e);
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

      $this->markUp();
      return array_unique($allKeys);
    } catch (Exception $e) {
      $this->markDown($e);
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
      $this->markUp();
      return true;
    } catch (Exception $e) {
      $this->markDown($e);
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
      $result = $this->client->exists($key) > 0;
      $this->markUp();
      return $result;
    } catch (Exception $e) {
      $this->markDown($e);
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
      $result = $this->client->lpush($key, [$value]);
      $this->markUp();
      return $result;
    } catch (Exception $e) {
      $this->markDown($e);
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
      $result = $this->client->rpop($key);
      $this->markUp();
      return $result;
    } catch (Exception $e) {
      $this->markDown($e);
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
      $result = $this->client->incr($key);
      $this->markUp();
      return $result;
    } catch (Exception $e) {
      $this->markDown($e);
      return false;
    }
  }

  /**
   * Alias for increment
   * 
   * @param string $key
   * @return int|false
   */
  public function incr($key)
  {
    return $this->increment($key);
  }
}
