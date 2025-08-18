<?php
namespace App\Lib;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Cronhooks
{
  private $apiKey;
  private $baseUrl;
  private $callbackUrl;
  private $client;

  public function __construct()
  {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();

    $this->apiKey      = $_ENV['CRONHOOKS_API_KEY'] ?? '';
    $this->baseUrl     = $_ENV['CRONHOOKS_BASE_URL'] ?: 'https://api.cronhooks.io';
    $this->callbackUrl = $_ENV['CRONHOOKS_CALLBACK'] ?? '';

    $this->client  = new Client([
      'base_uri' => rtrim($this->baseUrl, '/'),
      'timeout'  => 15,
    ]);
  }

  /**
   * @param string|int $articleId
   * @param string $publishTime  // "Y-m-d H:i:s" (waktu lokal Asia/Jakarta)
   * @param string $callbackUrl
   * @param array  $opts [
   *   'title' => string,
   *   'timezone' => 'Asia/Jakarta',
   *   'method' => 'POST',
   *   'contentType' => 'application/json; charset=utf-8',
   *   'headers' => array,
   *   'payload' => array|string,   // body yang dikirim ke webhook-mu
   *   'sendCronhookObject' => bool, // default true
   *   'sendFailureAlert' => bool,   // default true
   *   'groupId' => string|null,
   *   'retryCount' => int|null,           // optional (ad-hoc)
   *   'retryIntervalSeconds' => int|null, // optional (ad-hoc)
   * ]
   */
  public function createSchedule(string $publishTime, array $opts = []): array
  {
    // --- siapkan default option
    $title       = $opts['title'] ?? "Trigger";
    $timezone    = $opts['timezone'] ?? 'Asia/Jakarta';
    $method      = $opts['method'] ?? 'POST';
    $contentType = $opts['contentType'] ?? 'application/json; charset=utf-8';
    $headers     = $opts['headers'] ?? [];
    $payload     = $opts['payload'];
    $sendObj     = array_key_exists('sendCronhookObject', $opts) ? (bool)$opts['sendCronhookObject'] : true;
    $sendAlert   = array_key_exists('sendFailureAlert', $opts)   ? (bool)$opts['sendFailureAlert']   : true;
    $groupId     = $opts['groupId'] ?? null;
    $retryCount  = $opts['retryCount'] ?? null;
    $retryIntSec = $opts['retryIntervalSeconds'] ?? null;

    // --- format runAt TANPA offset (docs: tidak boleh ada Z/offset)
    $dt = new \DateTime($publishTime, new \DateTimeZone($timezone));
    $runAt = $dt->format('Y-m-d\TH:i:s'); // contoh: 2025-08-21T07:00:00

    // --- rakit payload sesuai docs
    $body = [
      'groupId'            => $groupId,
      'title'              => $title,
      'url'                => $this->callbackUrl,
      'timezone'           => $timezone,
      'method'             => strtoupper($method),
      'headers'            => (object)$headers,           // object kosong kalau tak ada
      'payload'            => $payload,                   // boleh object/string
      'contentType'        => $contentType,
      'isRecurring'        => false,                      // ad-hoc
      'cronExpression'     => '',                         // wajib ada, kosong utk ad-hoc
      'runAt'              => $runAt,                     // <= TANPA offset!
      'sendCronhookObject' => $sendObj,
      'sendFailureAlert'   => $sendAlert,
    ];

    // optional retry params utk ad-hoc
    if ($retryCount !== null) $body['retryCount'] = (string)$retryCount;
    if ($retryIntSec !== null) $body['retryIntervalSeconds'] = (string)$retryIntSec;

    $res = $this->client->post('/schedules', [
      'headers' => [
        'Authorization' => "Bearer {$this->apiKey}",
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
      ],
      'json' => $body,
    ]);

    return json_decode((string)$res->getBody(), true);
  }

  public function listSchedules(int $skip = 0, int $limit = 10): array
  {
    $res = $this->client->get('/schedules', [
      'headers' => [
        'Authorization' => "Bearer {$this->apiKey}",
        'Accept'        => 'application/json',
      ],
      'query' => ['skip' => $skip, 'limit' => $limit]
    ]);
    return json_decode((string)$res->getBody(), true);
  }

  public function deleteSchedule(string $scheduleId): bool
  {
    $res = $this->client->delete("/schedules/{$scheduleId}", [
      'headers' => [
        'Authorization' => "Bearer {$this->apiKey}",
        'Accept'        => 'application/json',
      ],
    ]);

    // Docs tunjukkan response 200 OK; amankan juga 204
    return in_array($res->getStatusCode(), [200, 204], true);
  }
}