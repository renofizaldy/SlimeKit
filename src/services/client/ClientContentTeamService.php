<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentTeam;

class ClientContentTeamService
{
  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary
  ) {}

  public function list(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? ($input['limit'] ?? 10)));

    $query = ContentTeam::with(['picture:id,original,thumbnail'])
      ->orderBy('id', 'ASC');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'name'    => $row->name,
        'title'   => $row->title,
        'link'    => $row->link,
        'picture' => [
          'original'  => $row->picture->original ?? null,
          'thumbnail' => $row->picture->thumbnail ?? null
        ]
      ]
    );
  }
}