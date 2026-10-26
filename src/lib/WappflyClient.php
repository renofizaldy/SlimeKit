<?php

namespace App\Lib;

use GuzzleHttp\Client;
use Throwable;

class WappflyClient
{
  private Client $client;
  private string $apiToken;

  public function __construct(array $config, ?Client $client = null)
  {
    $this->apiToken = $config['api_token'] ?? '';
    $baseUrl        = rtrim($config['base_url'] ?? 'https://wappfly.com/api', '/') . '/';

    $this->client = $client ?? new Client([
      'base_uri'        => $baseUrl,
      'connect_timeout' => 2.0,
      'timeout'         => 5.0,
      'headers'         => [
        'X-API-Token'  => $this->apiToken,
        'Content-Type' => 'application/json',
      ],
    ]);
  }

  /**
   * Kirim pesan teks via Wappfly API
   *
   * @param string $targetPhone Nomor HP tujuan (format lokal/internasional)
   * @param string $message Isi pesan teks
   * @param bool $wait False = async/instan tanpa menunggu WhatsApp ack
   * @return array
   */
  public function sendTextMessage(string $targetPhone, string $message, bool $wait = false): array
  {
    if (empty($this->apiToken) || empty($targetPhone)) {
      return [
        'status' => 'skipped',
        'reason' => 'Missing token or target phone',
      ];
    }

    $jid = $this->formatJid($targetPhone);

    $response = $this->client->post('messages/send', [
      'json' => [
        'jid'     => $jid,
        'message' => $message,
        'wait'    => $wait,
      ],
    ]);

    $statusCode = $response->getStatusCode();
    $body = json_decode((string) $response->getBody(), true) ?? [];

    return [
      'status'      => ($statusCode >= 200 && $statusCode < 300) ? 'success' : 'failed',
      'status_code' => $statusCode,
      'response'    => $body,
    ];
  }

  /**
   * Normalisasi nomor telepon ke format JID WhatsApp (e.g. 628123xxx@s.whatsapp.net)
   */
  public function formatJid(string $phone): string
  {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '0')) {
      $clean = '62' . substr($clean, 1);
    }
    return $clean . '@s.whatsapp.net';
  }
}
