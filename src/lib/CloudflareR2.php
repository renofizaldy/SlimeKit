<?php

namespace App\Lib;

require __DIR__.'/../../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class CloudflareR2
{
  private $client;
  private $bucket;
  private $endpoint;

  public function __construct()
  {
    $this->bucket = $_ENV['R2_BUCKET'];
    $this->endpoint = $_ENV['R2_ENDPOINT'];

    $this->client = new S3Client([
      'version' => 'latest',
      'region'  => $_ENV['R2_REGION'],
      'endpoint' => $this->endpoint,
      'credentials' => [
        'key'    => $_ENV['R2_ACCESS_KEY'],
        'secret' => $_ENV['R2_SECRET_KEY'],
      ],
    ]);
  }

  public function upload($key, $body, $contentType = 'application/octet-stream')
  {
    try {
      $result = $this->client->putObject([
        'Bucket' => $this->bucket,
        'Key'    => $key,
        'Body'   => $body,
        'ContentType' => $contentType
      ]);
      return $key;
    } catch (AwsException $e) {
      return $e->getMessage();
    }
  }

  public function delete($key)
  {
    try {
      $this->client->deleteObject([
        'Bucket' => $this->bucket,
        'Key'    => $key,
      ]);
      return true;
    } catch (AwsException $e) {
      return $e->getMessage();
    }
  }

  public function listFiles($prefix = '')
  {
    try {
      $result = $this->client->listObjectsV2([
        'Bucket' => $this->bucket,
        'Prefix' => $prefix
      ]);

      $files = [];
      if (isset($result['Contents'])) {
        foreach ($result['Contents'] as $object) {
          $files[] = $object['Key'];
        }
      }
      return $files;
    } catch (AwsException $e) {
      return $e->getMessage();
    }
  }

  public function generatePublicUrl($key)
  {
    // Public URL langsung (tanpa signed URL)
    return rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . $key;
  }

  public function generateSignedUrl($key, $expires = "+1 hour")
  {
    try {
      $cmd = $this->client->getCommand('GetObject', [
        'Bucket' => $this->bucket,
        'Key' => $key,
      ]);

      $request = $this->client->createPresignedRequest($cmd, $expires);
      return (string) $request->getUri();
    } catch (AwsException $e) {
      return $e->getMessage();
    }
  }
}
