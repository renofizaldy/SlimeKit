<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class AdminContentFAQService
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

    $query = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->executeQuery()
      ->fetchAllAssociative();

    if (!empty($query)) {
      $data = $query;
    }

    return $data;
  }

  public function add(array $input, array $user)
  {
    $this->db->beginTransaction();
    try {
      //? INSERT TO table
        $this->db->createQueryBuilder()
          ->insert($this->tableMain)
          ->setValue('title', ':title')
          ->setValue('description', ':description')
          ->setValue('created_at', ':created_at')
          ->setValue('updated_at', ':updated_at')
          ->setParameter('title', $input['title'])
          ->setParameter('description', $input['description'])
          ->setParameter('created_at', date('Y-m-d H:i:s'))
          ->setParameter('updated_at', date('Y-m-d H:i:s'))
          ->executeStatement();
      //? INSERT TO table

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, $this->db->lastInsertId(), 'INSERT');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    $this->db->beginTransaction();
    try {
      //? UPDATE ON table
        $this->db->createQueryBuilder()
          ->update($this->tableMain)
          ->set('title', ':title')
          ->set('description', ':description')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameter('title', $input['title'])
          ->setParameter('description', $input['description'])
          ->setParameter('id', (int) $input['id'])
          ->setParameter('updated_at', date('Y-m-d H:i:s'))
          ->executeStatement();
      //? UPDATE ON table

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, (int) $input['id'], 'UPDATE');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function sort(array $input, array $user)
  {
    $this->db->beginTransaction();
    try {
      foreach($input['order'] as $row) {
        //? UPDATE ON table
          $this->db->createQueryBuilder()
            ->update($this->tableMain)
            ->set('sort', ':sort')
            ->where('id = :id')
            ->setParameter('sort', (int) $row['sort'])
            ->setParameter('id', (int) $row['id'])
            ->executeStatement();
        //? UPDATE ON table

        //? LOG Record
          $this->helper->addLog($this->db, $user, $this->tableMain, (int) $row['id'], 'UPDATE');
        //? LOG Record
      }

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function drop(array $input, array $user)
  {
    $check = $this->checkExist($input);

    $this->db->beginTransaction();
    try {
      //? DELETE table
      $this->db->createQueryBuilder()
        ->delete($this->tableMain)
        ->where('id = :id')
        ->setParameter('id', $check['id'])
        ->executeStatement();
      //? DELETE table

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, (int) $check['id'], 'DELETE');
      //? LOG Record

      $this->db->commit();
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }
}