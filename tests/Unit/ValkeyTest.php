<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Lib\Valkey;

class ValkeyTest extends TestCase
{
  private string $tempMarkerFile;

  protected function setUp(): void
  {
    parent::setUp();
    $this->tempMarkerFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'valkey_test_' . uniqid() . '.marker';
  }

  protected function tearDown(): void
  {
    if (file_exists($this->tempMarkerFile)) {
      @unlink($this->tempMarkerFile);
    }
    parent::tearDown();
  }

  public function test_valkey_can_be_instantiated_with_dummy_config()
  {
    $valkey = new Valkey([
      'scheme' => 'tcp',
      'host' => '127.0.0.1',
      'port' => 6379,
      'username' => null,
      'password' => null,
    ], null, $this->tempMarkerFile);

    $this->assertInstanceOf(Valkey::class, $valkey);
    $this->assertSame($this->tempMarkerFile, $valkey->getCircuitBreakerFile());
  }

  public function test_mark_down_creates_marker_file_and_sets_disconnected()
  {
    $valkey = new Valkey([
      'scheme' => 'tcp',
      'host' => '127.0.0.1',
      'port' => 6379,
      'username' => null,
      'password' => null,
    ], null, $this->tempMarkerFile);

    $this->assertFileDoesNotExist($this->tempMarkerFile);

    $valkey->markDown();

    $this->assertFalse($valkey->isConnected());
    $this->assertFileExists($this->tempMarkerFile);
    $this->assertTrue($valkey->isCircuitBreakerOpen());
  }

  public function test_mark_up_removes_marker_file_and_sets_connected()
  {
    touch($this->tempMarkerFile);
    $this->assertFileExists($this->tempMarkerFile);

    $valkey = new Valkey([
      'scheme' => 'tcp',
      'host' => '127.0.0.1',
      'port' => 6379,
      'username' => null,
      'password' => null,
    ], null, $this->tempMarkerFile);

    $valkey->markUp();

    $this->assertTrue($valkey->isConnected());
    $this->assertFileDoesNotExist($this->tempMarkerFile);
    $this->assertFalse($valkey->isCircuitBreakerOpen());
  }

  public function test_constructor_bypasses_connection_when_circuit_breaker_is_open()
  {
    touch($this->tempMarkerFile);

    // Provide invalid host/port that would fail/timeout if connection was attempted
    $valkey = new Valkey([
      'scheme' => 'tcp',
      'host' => '192.0.2.1', // TEST-NET non-routable IP
      'port' => 9999,
      'username' => null,
      'password' => null,
    ], null, $this->tempMarkerFile);

    $this->assertFalse($valkey->isConnected());
    $this->assertTrue($valkey->isCircuitBreakerOpen());
  }

  public function test_circuit_breaker_expires_after_cooldown_duration()
  {
    touch($this->tempMarkerFile, time() - (Valkey::DOWN_COOLDOWN + 5));

    $valkey = new Valkey([
      'scheme' => 'tcp',
      'host' => '127.0.0.1',
      'port' => 6379,
      'username' => null,
      'password' => null,
    ], null, $this->tempMarkerFile);

    $this->assertFalse($valkey->isCircuitBreakerOpen());
  }

  public function test_operations_return_default_when_not_connected()
  {
    touch($this->tempMarkerFile);

    $valkey = new Valkey([
      'scheme' => 'tcp',
      'host' => '127.0.0.1',
      'port' => 6379,
      'username' => null,
      'password' => null,
    ], null, $this->tempMarkerFile);

    $this->assertNull($valkey->get('test_key'));
    $this->assertFalse($valkey->set('test_key', 'val'));
    $this->assertFalse($valkey->delete('test_key'));
    $this->assertFalse($valkey->deleteByPrefix('test_prefix'));
    $this->assertSame([], $valkey->getKeysByPrefix('test_prefix'));
    $this->assertFalse($valkey->deleteAll());
    $this->assertFalse($valkey->exists('test_key'));
    $this->assertFalse($valkey->lpush('queue', 'val'));
    $this->assertNull($valkey->rpop('queue'));
    $this->assertFalse($valkey->increment('counter'));
    $this->assertFalse($valkey->incr('counter'));
  }
}
