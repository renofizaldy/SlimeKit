<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;

class AdminContentArticleService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $tableMain = 'tb_content_article';

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

    $queryBuilder = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain);

    if ($input['status'] !== 'all') {
      $queryBuilder->where('status = :status')
        ->setParameter('status', $input['status']);
    }
    $query = $queryBuilder->executeQuery()->fetchAllAssociative();

    if (!empty($query)) {
      foreach ($query as $row) {
        $data[] = [
          'id'      => $row['id'],
          'status'  => $row['status'],
          'title'   => $row['title'],
          'author'  => $row['author'],
          'publish' => date('Y-m-d H:i:s', strtotime($row['publish']))
        ];
      }
    }

    return $data;
  }

  public function detail(array $input)
  {
    $data = [];
    $detail = $this->checkExist($input);

    $query = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.*',
        'tb_picture.original AS picture_original',
        'tb_picture.thumbnail AS picture_thumbnail'
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        'tb_picture',
        'tb_picture',
        $this->tableMain.'.id_picture = tb_picture.id'
      )
      ->where($this->tableMain.'.id = :id')
      ->setParameter('id', (int) $detail['id'])
      ->executeQuery()
      ->fetchAssociative();

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
          ->setValue('id_picture', ':id_picture')
          ->setValue('status', ':status')
          ->setValue('title', ':title')
          ->setValue('description', ':description')
          ->setValue('author', ':author')
          ->setValue('publish', ':publish')
          ->setValue('created_at', ':created_at')
          ->setValue('updated_at', ':updated_at')
          ->setParameter('id_picture', $picture_id)
          ->setParameter('status', $input['status'])
          ->setParameter('title', $input['title'])
          ->setParameter('description', $input['description'])
          ->setParameter('author', $input['author'])
          ->setParameter('publish', date('Y-m-d H:i:s', strtotime($input['publish'])))
          ->setParameter('created_at', date('Y-m-d H:i:s'))
          ->setParameter('updated_at', date('Y-m-d H:i:s'))
          ->executeStatement();
        $articleId = $this->db->lastInsertId();
      //? INSERT TO table

      //? UPDATE ON table
        $this->db->createQueryBuilder()
          ->update($this->tableMain)
          ->set('slug', ':slug')
          ->where('id = :id')
          ->setParameter('slug', $this->helper->slugify($input['title'], $articleId))
          ->setParameter('id', $articleId)
          ->executeQuery();
      //? UPDATE ON table

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, $articleId, 'INSERT');
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
          ->set('status', ':status')
          ->set('title', ':title')
          ->set('description', ':description')
          ->set('author', ':author')
          ->set('publish', ':publish')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameter('id', (int) $input['id'])
          ->setParameter('status', $input['status'])
          ->setParameter('title', $input['title'])
          ->setParameter('description', $input['description'])
          ->setParameter('author', $input['author'])
          ->setParameter('publish', date('Y-m-d H:i:s', strtotime($input['publish'])))
          ->setParameter('updated_at', date('Y-m-d H:i:s'));
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

      //? DELETE picture
        if (!empty($check['id_picture'])) {
          $this->db->createQueryBuilder()
            ->delete('tb_picture')
            ->where('id = :id')
            ->setParameter('id', $check['id_picture'])
            ->executeStatement();
        }
      //? DELETE picture

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