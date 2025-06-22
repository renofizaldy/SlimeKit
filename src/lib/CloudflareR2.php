<?php

namespace App\Lib;

use Dotenv\Dotenv;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Symfony\Component\Mime\MimeTypes;

class CloudflareR2
{
  protected $client;
  protected $bucket;
  protected $mimeTypes;

  /**
   * Initialize CloudflareR2 client.
   */
  public function __construct()
  {
    $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
    $dotenv->safeLoad();

    $this->client = new S3Client([
      'region'      => $_ENV['R2_REGION'],
      'version'     => 'latest',
      'endpoint'    => $_ENV['R2_ENDPOINT'],
      'credentials' => [
        'key'    => $_ENV['R2_ACCESS_KEY_ID'],
        'secret' => $_ENV['R2_SECRET_ACCESS_KEY'],
      ],
    ]);
    $this->bucket = $_ENV['R2_BUCKET'];
    $this->mimeTypes = new MimeTypes();
  }

  /**
   * Upload file to Cloudflare R2.
   *
   * @param mixed $input Base64 string, file path, or stream resource.
   * @param string $prefix Optional prefix for the file path.
   * @return string Uploaded file key.
   * @throws \Exception
   */
  public function upload($input, $prefix = 'uploads/')
  {
    if (is_string($input) && $this->isBase64($input)) {
      return $this->uploadBase64($input, $prefix);
    }
    elseif (is_string($input) && file_exists($input)) {
      return $this->uploadFilePath($input, $prefix);
    }
    elseif (is_resource($input)) {
      return $this->uploadStream($input, $prefix);
    }
    else {
      throw new \Exception("Invalid input type for upload");
    }
  }

  /**
   * Delete file from Cloudflare R2.
   *
   * @param string $key File key to delete.
   * @return bool
   * @throws \Exception
   */
  public function delete($key)
  {
    try {
      $this->client->deleteObject([
        'Bucket' => $this->bucket,
        'Key'    => $key,
      ]);
      return true;
    }
    catch (AwsException $e) {
      throw new \Exception("Delete failed: " . $e->getMessage());
    }
  }

  /**
   * Upload Base64 encoded data.
   *
   * @param string $base64string Base64 encoded string with data URI.
   * @param string $prefix Optional prefix for the file path.
   * @return string Uploaded file key.
   * @throws \Exception
   */
  private function uploadBase64($base64string, $prefix)
  {
    preg_match('/^data:(.*?);base64,(.*)$/', $base64string, $matches);
    if (!$matches) {
      throw new \Exception("Invalid base64 format");
    }
    $contentType = $matches[1];
    $binaryData  = base64_decode($matches[2]);
    $extension   = $this->getExtensionFromMime($contentType);
    $key         = $this->generateKey($prefix, $extension);
    return $this->uploadToR2($key, $binaryData, $contentType);
  }

  /**
   * Upload file from file path.
   *
   * @param string $filePath Path to the local file.
   * @param string $prefix Optional prefix for the file path.
   * @return string Uploaded file key.
   * @throws \Exception
   */
  private function uploadFilePath($filePath, $prefix)
  {
    $binaryData  = file_get_contents($filePath);
    $contentType = mime_content_type($filePath);
    $extension   = pathinfo($filePath, PATHINFO_EXTENSION) ?: $this->getExtensionFromMime($contentType);
    $key         = $this->generateKey($prefix, $extension);
    return $this->uploadToR2($key, $binaryData, $contentType);
  }

  /**
   * Upload file from stream resource.
   *
   * @param resource $stream Resource handle.
   * @param string $prefix Optional prefix for the file path.
   * @return string Uploaded file key.
   * @throws \Exception
   */
  private function uploadStream($stream, $prefix)
  {
    $binaryData  = stream_get_contents($stream);
    $contentType = 'application/octet-stream';
    $extension   = 'bin';
    $key         = $this->generateKey($prefix, $extension);
    return $this->uploadToR2($key, $binaryData, $contentType);
  }

  /**
   * Core function to upload data to Cloudflare R2.
   *
   * @param string $key File key.
   * @param string $body File binary data.
   * @param string $contentType MIME type.
   * @return string Uploaded file key.
   * @throws \Exception
   */
  private function uploadToR2($key, $body, $contentType)
  {
    try {
      $this->client->putObject([
        'Bucket'      => $this->bucket,
        'Key'         => $key,
        'Body'        => $body,
        'ContentType' => $contentType
      ]);
      return $key;
    }
    catch (AwsException $e) {
      throw new \Exception($e->getMessage());
    }
  }

  /**
   * Check if string is base64 data URI.
   *
   * @param string $string Input string.
   * @return bool
   */
  private function isBase64($string)
  {
    return preg_match('/^data:(.*?);base64,/', $string);
  }

  /**
   * Generate unique file key.
   *
   * @param string $prefix Prefix path.
   * @param string $extension File extension.
   * @return string Unique file key.
   * @throws \Exception
   */
  private function generateKey($prefix, $extension = '')
  {
    $uniq = date('YmdHis') . '-' . bin2hex(random_bytes(4));
    return $prefix . $uniq . ($extension ? '.' . $extension : '');
  }

  /**
   * Get file extension from MIME type.
   *
   * @param string $mime MIME type.
   * @return string File extension.
   */
  private function getExtensionFromMime($mime)
  {
    $extensions = $this->mimeTypes->getExtensions($mime);
    return $extensions[0] ?? 'bin';
  }

  /**
   * List files in Cloudflare R2 bucket.
   *
   * @param string $prefix Filter files by prefix.
   * @return array List of file keys.
   * @throws \Exception
   */
  public function listFiles($prefix = '')
  {
    try {
      $result = $this->client->listObjectsV2([
        'Bucket' => $this->bucket,
        'Prefix' => $prefix,
      ]);

      $files = [];
      if (isset($result['Contents'])) {
        foreach ($result['Contents'] as $object) {
          $files[] = $object['Key'];
        }
      }

      return $files;
    } catch (AwsException $e) {
      throw new \Exception("List failed: " . $e->getMessage());
    }
  }

  /**
   * Generate signed URL for private access.
   *
   * @param string $key File key.
   * @param int $expiresIn Expiration time in seconds.
   * @return string Signed URL.
   * @throws \Exception
   */
  public function generateSignedUrl($key, $expiresIn = 3600)
  {
    try {
      $cmd = $this->client->getCommand('GetObject', [
        'Bucket' => $this->bucket,
        'Key'    => $key
      ]);
      $request = $this->client->createPresignedRequest($cmd, '+' . $expiresIn . ' seconds');
      return (string) $request->getUri();
    }
    catch (AwsException $e) {
      throw new \Exception($e->getMessage());
    }
  }

  /**
   * Generate presigned URL for direct upload (PUT).
   *
   * @param string $key File key.
   * @param string $contentType MIME type of file.
   * @param int $expiresIn Expiration time in seconds.
   * @return string Presigned URL.
   * @throws \Exception
   */
  public function generatePresignedUploadUrl($key, $contentType, $expiresIn = 3600)
  {
    try {
      $cmd = $this->client->getCommand('PutObject', [
        'Bucket'      => $this->bucket,
        'Key'         => $key,
        'ContentType' => $contentType
      ]);
      $request = $this->client->createPresignedRequest($cmd, '+' . $expiresIn . ' seconds');
      return (string) $request->getUri();
    }
    catch (AwsException $e) {
      throw new \Exception("Presigned upload URL failed: " . $e->getMessage());
    }
  }

  /**
   * Check if file exists in bucket.
   *
   * @param string $key File key.
   * @return bool True if exists, false otherwise.
   */
  public function exists($key)
  {
    try {
      $this->client->headObject([
        'Bucket' => $this->bucket,
        'Key'    => $key,
      ]);
      return true;
    }
    catch (AwsException $e) {
      return false;
    }
  }
}
