<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Lib\WapitoClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class WapitoClientTest extends TestCase
{
  public function test_format_phone_normalizes_numbers()
  {
    $client = new WapitoClient([
      'api_token' => 'test_token',
    ]);

    $this->assertEquals('+628123456789', $client->formatPhone('08123456789'));
    $this->assertEquals('+628123456789', $client->formatPhone('+62 812-3456-789'));
    $this->assertEquals('+628999999999', $client->formatPhone('628999999999'));
  }

  public function test_send_text_message_skips_when_empty_token()
  {
    $client = new WapitoClient([
      'api_token' => '',
    ]);

    $result = $client->sendTextMessage('08123456789', 'Hello');
    $this->assertEquals('skipped', $result['status']);
    $this->assertEquals('Missing token or target phone', $result['reason']);
  }

  public function test_send_text_message_success_with_mock()
  {
    $mock = new MockHandler([
      new Response(201, ['Content-Type' => 'application/json'], json_encode(['status' => 'sent', 'id' => 'msg_123'])),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $mockClient   = new Client(['handler' => $handlerStack]);

    $client = new WapitoClient([
      'api_token' => 'wpt_valid_token',
    ], $mockClient);

    $result = $client->sendTextMessage('08123456789', 'Halo ini pesan tes');
    $this->assertEquals('success', $result['status']);
    $this->assertEquals(201, $result['status_code']);
    $this->assertEquals('sent', $result['response']['status']);
  }
}
