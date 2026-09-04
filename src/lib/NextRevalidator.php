<?php

namespace App\Lib;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class NextRevalidator
{
  private $secret;
  private $endpoints;
  private $client;

  public function __construct(array $config)
  {
    $this->secret    = $config['secret'] ?? '';
    $this->endpoints = $this->parseEndpoints($config['endpoints'] ?? '');
    $this->client    = new Client(['timeout' => 5]);
  }

  private function parseEndpoints(string $raw): array
  {
    $endpoints = [];
    $items = array_filter(array_map('trim', explode(',', $raw)));

    foreach ($items as $item) {
      $parts = explode('=', $item, 2);
      if (count($parts) !== 2) {
        continue;
      }

      $site = trim($parts[0]);
      $url  = trim($parts[1]);
      if ($site && $url) {
        $endpoints[$site] = $url;
      }
    }

    return $endpoints;
  }

  public function revalidateArticle(string $site, string $slug, ?string $oldSlug = null): bool
  {
    if (empty($this->secret) || empty($this->endpoints[$site]) || empty($slug)) {
      return false;
    }

    try {
      $this->client->post($this->endpoints[$site], [
        'headers' => [
          'Accept' => 'application/json',
        ],
        'json' => [
          'secret'   => $this->secret,
          'site'     => $site,
          'slug'     => $slug,
          'old_slug' => $oldSlug,
        ],
      ]);

      return true;
    }
    catch (GuzzleException $e) {
      error_log("Next revalidate failed for {$site}/{$slug}: " . $e->getMessage());
      return false;
    }
  }
}
