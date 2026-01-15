<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentGallery;

class ClientContentGalleryService
{
  private $helper;
  private $cloudinary;

  public function __construct()
  {
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  public function list(array $input)
  {
    $data  = [];
    $limit = max(0, (int) ($input['limit'] ?? 10));

    $query = ContentGallery::with(['picture:id,original,thumbnail'])
      ->orderBy('created_at', 'DESC')
      ->limit($limit > 0 ? $limit : null)
      ->get();

    if (!$query->isEmpty()) {
      foreach ($query as $row) {
        $data[] = [
          'created_at'  => date('Y-m-d H:i:s', strtotime($row->created_at)),
          'name'        => $row->name,
          'description' => $row->description,
          'picture'     => [
            'original'  => $row->picture->original,
            'thumbnail' => $row->picture->thumbnail
          ]
        ];
      }
    }

    return $data;
  }
}