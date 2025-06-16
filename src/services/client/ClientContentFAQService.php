<?php

namespace App\Services\Client;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class ClientContentFAQService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_faq';

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
      ->select('*')
      ->from($this->tableMain)
      ->orderBy('sort', 'ASC')
      ->setMaxResults($limit > 0 ? $limit : null)
      ->executeQuery()
      ->fetchAllAssociative();

    if (!empty($query)) {
      foreach($query as $row) {
        $data[] = [
          'sort'        => (int) $row['sort'],
          'title'       => $row['title'],
          'description' => $row['description']
        ];
      }
    }

    return $data;
  }
}