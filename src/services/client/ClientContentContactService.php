<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentContact;

class ClientContentContactService
{
  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  public function list(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? ($input['limit'] ?? 10)));

    $query = ContentContact::orderBy('id', 'asc');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => $row->toArray()
    );
  }

  public function detail(array $input)
  {
    $query = ContentContact::where('name', $input['name'])->first();
    if (!$query) return [];

    return [
      'name'  => $query->name,
      'value' => $query->value
    ];
  }
}