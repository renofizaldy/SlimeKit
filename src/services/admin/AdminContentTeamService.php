<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class AdminContentTeamService
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
          ->values([
            'id_picture' => ':id_picture',
            'name'       => ':name',
            'title'      => ':title',
            'link'       => ':link',
          ])
          ->setParameters([
            'id_picture' => $picture_id,
            'name'       => $input['name'],
            'title'      => $input['title'],
            'link'       => null,
          ])
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
          ->set('title', ':title')
          ->set('link', ':link')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameters([
            'id'         => (int) $input['id'],
            'name'       => $input['name'],
            'title'      => $input['title'],
            'link'       => null,
            'updated_at' => date('Y-m-d H:i:s')
          ]);
        if (!empty($picture_id)) {
          $update->set('id_picture', ':id_picture')->setParameter('id_picture', $picture_id);
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