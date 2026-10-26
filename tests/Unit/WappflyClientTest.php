<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Lib\WappflyClient;

class WappflyClientTest extends TestCase
{
  public function test_format_jid_normalizes_numbers()
  {
    $client = new WappflyClient([
      'api_token' => 'test_token',
    ]);

    $this->assertEquals('628123456789@s.whatsapp.net', $client->formatJid('08123456789'));
    $this->assertEquals('628123456789@s.whatsapp.net', $client->formatJid('+62 812-3456-789'));
    $this->assertEquals('628999999999@s.whatsapp.net', $client->formatJid('628999999999'));
  }

  public function test_send_text_message_skips_when_empty_token()
  {
    $client = new WappflyClient([
      'api_token' => '',
    ]);

    $result = $client->sendTextMessage('08123456789', 'Hello');
    $this->assertEquals('skipped', $result['status']);
  }
}
