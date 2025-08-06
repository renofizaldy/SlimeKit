<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class AdminContentGalleryService
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

  public function list()
  {
    $data = [];

    $query = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.id',
        $this->tableMain.'.name AS title',
        $this->tableMain.'.description',
        $this->tableMain.'.updated_at',
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
      //? PICTURE UPLOAD
        $picture_id = $this->helper->pictureUpload($this->db, $this->cloudinary, $input['picture'] ?? null);
      //? PICTURE UPLOAD

      //? INSERT TO table
        $this->db->createQueryBuilder()
          ->insert($this->tableMain)
          ->setValue('name', ':name')
          ->setValue('description', ':description')
          ->setValue('id_picture', ':id_picture')
          ->setValue('created_at', ':created_at')
          ->setValue('updated_at', ':updated_at')
          ->setParameter('name', $input['title'])
          ->setParameter('description', $input['description'])
          ->setParameter('id_picture', $picture_id['id'])
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
      //? PICTURE UPLOAD
        $picture_id = $this->helper->pictureUpload($this->db, $this->cloudinary, $input['picture'] ?? null);
      //? PICTURE UPLOAD

      //? UPDATE ON table
        $update = $this->db->createQueryBuilder()
          ->update($this->tableMain)
          ->set('name', ':name')
          ->set('description', ':description')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameter('name', $input['title'])
          ->setParameter('description', $input['description'])
          ->setParameter('updated_at', date('Y-m-d H:i:s'))
          ->setParameter('id', (int) $input['id']);
        if (!empty($picture_id)) {
          $update->set('id_picture', ':id_picture')->setParameter('id_picture', $picture_id['id']);
        }
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

      //? DELETE PICTURE
        $this->db->createQueryBuilder()
          ->delete('tb_picture')
          ->where('id = :id')
          ->setParameter('id', $check['id_picture'])
          ->executeStatement();
      //? DELETE PICTURE

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