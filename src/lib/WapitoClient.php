<?php

namespace App\Lib;

use GuzzleHttp\Client;
use Throwable;

class WapitoClient
{
  private Client $client;
  private string $apiToken;

  public function __construct(array $config, ?Client $client = null)
  {
    $this->apiToken = $config['api_token'] ?? '';
    $baseUrl        = rtrim($config['base_url'] ?? 'https://api.wapito.com', '/') . '/';
    $this->client   = $client ?? new Client([
      'base_uri'        => $baseUrl,
      'connect_timeout' => 2.0,
      'timeout'         => 5.0,
      'headers'         => [
        'Authorization' => 'Bearer ' . $this->apiToken,
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
      ],
    ]);
  }

  /**
   * Kirim pesan teks via Wapito.com REST API
   *
   * @param string $targetPhone Nomor HP tujuan (format lokal/internasional)
   * @param string $message Isi pesan teks
   * @return array
   */
  public function sendTextMessage(string $targetPhone, string $message): array
  {
    if (empty($this->apiToken) || empty($targetPhone)) {
      return [
        'status' => 'skipped',
        'reason' => 'Missing token or target phone',
      ];
    }

    $to = $this->formatPhone($targetPhone);

    $response = $this->client->post('v1/messages/text', [
      'json' => [
        'to'   => $to,
        'body' => $message,
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
   * Normalisasi nomor telepon ke format internasional E.164 (+62xxx)
   */
  public function formatPhone(string $phone): string
  {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '0')) {
      $clean = '62' . substr($clean, 1);
    }
    return '+' . $clean;
  }
}
