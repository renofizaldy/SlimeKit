<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\General;
use App\Lib\Valkey;
use App\Lib\WapitoClient;
use App\Lib\WappflyClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class GeneralWhatsAppFailoverTest extends TestCase
{
  private function createMockValkey()
  {
    return $this->createMock(Valkey::class);
  }

  public function test_send_whatsapp_uses_wapito_when_successful()
  {
    $mockWapitoHttp = new MockHandler([
      new Response(201, ['Content-Type' => 'application/json'], json_encode(['status' => 'sent'])),
    ]);
    $wapitoClient = new WapitoClient(['api_token' => 'wpt_token'], new Client(['handler' => HandlerStack::create($mockWapitoHttp)]));

    // Wappfly should not be called
    $mockWappflyHttp = new MockHandler([]);
    $wappflyClient = new WappflyClient(['api_token' => 'wpf_token'], new Client(['handler' => HandlerStack::create($mockWappflyHttp)]));

    $general = new General($this->createMockValkey(), $wapitoClient, $wappflyClient);

    $res = $general->sendWhatsApp('08123456789', 'Halo');
    $this->assertEquals('success', $res['status']);
    $this->assertEquals('wapito', $res['provider']);
  }

  public function test_send_whatsapp_failover_to_wappfly_when_wapito_fails()
  {
    // Wapito returns 500 error
    $mockWapitoHttp = new MockHandler([
      new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'server error'])),
    ]);
    $wapitoClient = new WapitoClient(['api_token' => 'wpt_token'], new Client(['handler' => HandlerStack::create($mockWapitoHttp)]));

    // Wappfly succeeds
    $mockWappflyHttp = new MockHandler([
      new Response(200, ['Content-Type' => 'application/json'], json_encode(['success' => true])),
    ]);
    $wappflyClient = new WappflyClient(['api_token' => 'wpf_token'], new Client(['handler' => HandlerStack::create($mockWappflyHttp)]));

    $general = new General($this->createMockValkey(), $wapitoClient, $wappflyClient);

    $res = $general->sendWhatsApp('08123456789', 'Halo');
    $this->assertEquals('success', $res['status']);
    $this->assertEquals('wappfly', $res['provider']);
  }

  public function test_send_whatsapp_gracefully_skips_when_no_token()
  {
    $wapitoClient  = new WapitoClient(['api_token' => '']);
    $wappflyClient = new WappflyClient(['api_token' => '']);

    $general = new General($this->createMockValkey(), $wapitoClient, $wappflyClient);

    $res = $general->sendWhatsApp('08123456789', 'Halo');
    $this->assertEquals('failed', $res['status']);
    $this->assertEquals('No WhatsApp provider active or configured', $res['reason']);
  }
}
