<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class AdminContentContactService
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
          ->setValue('name', ':name')
          ->setValue('value', ':value')
          ->setValue('created_at', ':created_at')
          ->setValue('updated_at', ':updated_at')
          ->setParameter('name', $input['name'])
          ->setParameter('value', $input['value'])
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
        $update = $this->db->createQueryBuilder()
          ->update($this->tableMain)
          ->set('value', ':value')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameter('value', $input['value'])
          ->setParameter('updated_at', date('Y-m-d H:i:s'))
          ->setParameter('id', (int) $input['id']);
        $update->executeStatement();
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