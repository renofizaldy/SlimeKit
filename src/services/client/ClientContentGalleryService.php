<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentGallery;

class ClientContentGalleryService
{
  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  public function list(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? ($input['limit'] ?? 10)));

    $query = ContentGallery::with(['picture:id,original,thumbnail'])
      ->orderBy('created_at', 'DESC');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'created_at'  => date('Y-m-d H:i:s', strtotime($row->created_at)),
        'name'        => $row->name,
        'description' => $row->description,
        'picture'     => [
          'original'  => $row->picture->original ?? null,
          'thumbnail' => $row->picture->thumbnail ?? null
        ]
      ]
    );
  }
}