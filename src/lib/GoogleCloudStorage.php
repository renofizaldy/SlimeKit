<?php

namespace App\Lib;

use Google\Cloud\Storage\StorageClient;
use Exception;

class GoogleCloudStorage
{
  private $storage;
  private $bucket;

  public function __construct(array $config)
  {
    $this->bucket  = $config['bucket'];
    $this->storage = new StorageClient([
      'keyFilePath' => $config['credentials_path'],
    ]);
  }

  public function storage($action, $bucketName = null, $filestream = null, $destination = null)
  {
    $targetBucket = !empty($bucketName) ? $bucketName : $this->bucket;
    $bucket = $this->storage->bucket($targetBucket);

    switch ($action) {
      case 'delete':
        try {
          foreach ($bucket->objects() as $object) {
            $object->delete();
          }
          return "All files in '$targetBucket' are deleted.";
        } catch (Exception $e) {
          return $e->getMessage();
        }
      case 'upload':
        try {
          $source = (is_string($filestream) && file_exists($filestream))
            ? fopen($filestream, 'rb')
            : $filestream;

          $bucket->upload(
            $source,
            [
              'name' => $destination
            ]
          );
          return $destination;
        } catch (Exception $e) {
          return $e->getMessage();
        }
    }
  }
}