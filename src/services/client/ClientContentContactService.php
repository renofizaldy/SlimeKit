<?php

namespace App\Services\Client;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;

use App\Models\ContentContact;

class ClientContentContactService
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
    return ContentContact::all()->toArray();
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