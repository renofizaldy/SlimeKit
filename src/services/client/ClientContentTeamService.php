<?php

namespace App\Services\Client;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class ClientContentTeamService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_team';

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  private function checkExist(array $input)
  {
    $check = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->where('id = :id')
      ->setParameter('id', (int) $input['id'])
      ->fetchAssociative();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function list(array $input)
  {
    $data = [];

    $limit = max(0, (int) ($input['limit'] ?? 10));
    $query = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.*',
        'tb_picture.original AS original',
        'tb_picture.thumbnail AS thumbnail'
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        'tb_picture',
        'tb_picture',
        $this->tableMain.'.id_picture = tb_picture.id'
      )
      ->setMaxResults($limit > 0 ? $limit : null)
      ->executeQuery()
      ->fetchAllAssociative();

    if (!empty($query)) {
      foreach ($query as $row) {
        $data[] = [
          'name'    => $row['name'],
          'title'   => $row['title'],
          'picture' => [
            'original'  => $row['original'],
            'thumbnail' => $row['thumbnail']
          ]
        ];
      }
    }

    return $data;
  }
}