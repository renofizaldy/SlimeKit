<?php

namespace App\Lib;

class Cloudinary
{
  protected $appName;

  public function __construct(array $config)
  {
    \Cloudinary\Configuration\Configuration::instance($config['url']);
    $this->appName = $config['app_name'] ?? '';
  }

  public function upload($file, $opt=[])
  {
    if (empty($opt) || !isset($opt['folder'])) {
      $opt['folder'] = $this->appName;
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