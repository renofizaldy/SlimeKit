<?php

namespace App\Lib;

require __DIR__.'/../../vendor/autoload.php';

use Dotenv\Dotenv;

class Cloudinary
{
  public function __construct()
  {
    $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
    $dotenv->safeLoad();
    \Cloudinary\Configuration\Configuration::instance($_ENV['CLOUDINARY_URL']);
  }

  public function upload($file, $opt=[])
  {
    if (empty($opt) || !isset($opt['folder'])) {
      $opt['folder'] = $_ENV['APP_NAME'];
    }
    $cloudinary = new \Cloudinary\Api\Upload\UploadApi();
    $result = $cloudinary->upload($file, $opt);
    return $result;
  }

  public function delete($public_id)
  {
    $cloudinary = new \Cloudinary\Api\Upload\UploadApi();
    return $cloudinary->destroy($public_id);
  }
}