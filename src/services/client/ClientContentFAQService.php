<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentFAQ;

class ClientContentFAQService
{
  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  public function list(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? ($input['limit'] ?? 10)));

    $query = ContentFAQ::orderBy('sort', 'ASC');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'sort'        => (int) $row->sort,
        'title'       => $row->title,
        'description' => $row->description
      ]
    );
  }
}