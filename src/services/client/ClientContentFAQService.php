<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentFAQ;

class ClientContentFAQService
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

    $query = ContentFAQ::orderBy('sort', 'ASC')
      ->limit($limit > 0 ? $limit : null)
      ->get();

    if (!$query->isEmpty()) {
      foreach($query as $row) {
        $data[] = [
          'sort'        => (int) $row->sort,
          'title'       => $row->title,
          'description' => $row->description
        ];
      }
    }

    return $data;
  }
}