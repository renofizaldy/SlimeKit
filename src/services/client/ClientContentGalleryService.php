<?php

namespace App\Services\Client;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class ClientContentGalleryService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_gallery';

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  public function list(array $input)
  {
    $data  = [];

    $limit = max(0, (int) ($input['limit'] ?? 10));
    $query = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.name',
        $this->tableMain.'.description',
        $this->tableMain.'.created_at',
        'tb_picture.original',
        'tb_picture.thumbnail'
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        'tb_picture',
        'tb_picture',
        $this->tableMain.'.id_picture = tb_picture.id'
      )
      ->orderBy('created_at', 'DESC')
      ->setMaxResults($limit > 0 ? $limit : null)
      ->executeQuery()
      ->fetchAllAssociative();

    if (!empty($query)) {
      foreach ($query as $row) {
        $data[] = [
          'created_at'  => date('Y-m-d H:i:s', strtotime($row['created_at'])),
          'name'        => $row['name'],
          'description' => $row['description'],
          'picture'     => [
            'original'  => $row['original'],
            'thumbnail' => $row['thumbnail']
          ]
        ];
      }
    }

    return $data;
  }
}