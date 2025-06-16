<?php

namespace App\Lib;

use Google\Cloud\Storage\StorageClient;
use Exception;

class GoogleCloud
{
  private $storage;

  public function __construct()
  {
    $this->storage = new StorageClient([
      'keyFilePath' => __DIR__ . '/../../assets/gcs-service-account.json',
    ]);
  }

  public function storage($action, $bucketName, $filestream = null, $destination = null)
  {
    $bucket = $this->storage->bucket($bucketName);

    switch ($action) {
      case 'delete':
        try {
          foreach ($bucket->objects() as $object) {
            $object->delete();
          }
          return "All files in '$bucketName' are deleted.";
        } catch (Exception $e) {
          return $e->getMessage();
        }
      case 'upload':
        try {
          $bucket->upload(
            $filestream,
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