<?php

namespace App\Lib;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class NextRevalidator
{
  private string $secret;
  private string $url;
  private Client $client;

  public function __construct(array $config)
  {
    $this->secret = $config['secret'] ?? '';
    $this->url    = $config['url'] ?? '';
    $this->client = new Client(['timeout' => 5]);
  }

  public function revalidateArticle(string $slug, ?string $oldSlug = null): bool
  {
    if (empty($this->secret) || empty($this->url) || empty($slug)) {
      return false;
    }

    try {
      $this->client->post($this->url, [
        'headers' => [
          'Accept' => 'application/json',
        ],
        'json' => [
          'secret'   => $this->secret,
          'slug'     => $slug,
          'old_slug' => $oldSlug,
        ],
      ]);

      return true;
    }
    catch (GuzzleException $e) {
      error_log("Next revalidate failed for {$slug}: " . $e->getMessage());
      return false;
    }
  }
}
