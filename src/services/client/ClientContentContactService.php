<?php

namespace App\Services\Client;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class ClientContentContactService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_contact';

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
  }

  public function list(array $input)
  {
    $data = [];

    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->executeQuery()
      ->fetchAllAssociative();

    if (!empty($query)) {
      foreach($query as $row) {
        $data[] = [
          'name'  => $row['name'],
          'value' => $row['value']
        ];
      }
    }

    return $data;
  }

  public function detail(array $input)
  {
    $data = [];

    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->where('name = :name')
      ->setParameter('name', $input['name'])
      ->executeQuery()
      ->fetchAssociative();

    if (!empty($query)) {
      foreach($query as $row) {
        $data[] = [
          'name'  => $row['name'],
          'value' => $row['value']
        ];
      }
    }

    return $data;
  }
}