<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;

class AdminArticleCategoryService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $valkey;
  private $tableMain = 'tb_article_category';
  private $tableSeoMeta = 'tb_seo_meta';
  private $tableArticle = 'tb_article';
  private $cacheKey = 'article_category';

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
    $this->valkey = new Valkey;
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

  private function checkArticleTotal(array $input)
  {
    $check = $this->db->createQueryBuilder()
      ->select('1')
      ->from($this->tableArticle)
      ->where('id_category = :id_category')
      ->setParameter('id_category', (int) $input['id'])
      ->setMaxResults(1)
      ->executeQuery()
      ->fetchOne();
    return (bool) $check;
  }

  public function checkSlug(array $input)
  {
    $check = $this->db->createQueryBuilder()
      ->select('id')
      ->from($this->tableMain)
      ->where('slug = :slug')
      ->setParameter('slug', $input['slug']);

    if (!empty($input['id'])) {
      $query = $check->fetchAssociative();
      if ($query && (int) $query['id'] !== (int) $input['id']) {
        throw new Exception('Slug already exists', 409);
      }
      return true;
    }

    if ($check->fetchOne()) {
      throw new Exception('Slug already exists', 409);
    }
    return true;
  }

  public function list(array $input)
  {
    $data = [];

    $queryBuilder = $this->db->createQueryBuilder()
      ->select(
        "{$this->tableMain}.*",
        "{$this->tableSeoMeta}.meta_title",
        "{$this->tableSeoMeta}.meta_description",
        "{$this->tableSeoMeta}.meta_robots",
        "(
          SELECT COUNT(*)
          FROM tb_article
          WHERE tb_article.id_category = {$this->tableMain}.id
        ) as total_article"
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tableSeoMeta,
        $this->tableSeoMeta,
        "{$this->tableSeoMeta}.id_parent = {$this->tableMain}.id AND {$this->tableSeoMeta}.type = 'article_category'"
      );

    if ($input['status'] !== 'all') {
      $queryBuilder->where('status = :status')
        ->setParameter('status', $input['status']);
    }
    $query = $queryBuilder->executeQuery()->fetchAllAssociative();

    if (!empty($query)) {
      foreach ($query as $row) {
        $data[] = [
          'id'               => $row['id'],
          'title'            => $row['title'],
          'status'           => $row['status'],
          'slug'             => $row['slug'],
          'description'      => $row['description'],
          'meta_title'       => $row['meta_title'],
          'meta_description' => $row['meta_description'],
          'meta_robots'      => $row['meta_robots'],
          'total'            => $row['total_article'],
          'created_at'       => date('Y-m-d H:i:s', strtotime($row['created_at']))
        ];
      }
    }

    return $data;
  }

  public function add(array $input, array $user)
  {
    $this->checkSlug($input);

    $this->db->beginTransaction();
    try {
      //? INSERT TO tableMain
        $this->db->createQueryBuilder()
          ->insert($this->tableMain)
          ->values([
            'status'      => ':status',
            'title'       => ':title',
            'description' => ':description',
            'slug'        => ':slug',
            'created_at'  => ':created_at',
            'updated_at'  => ':updated_at'
          ])
          ->setParameters([
            'status'      => $input['status'],
            'title'       => $input['title'],
            'description' => $input['description'],
            'slug'        => $input['slug'],
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
          ])
          ->executeStatement();
        $lastTableMainId = $this->db->lastInsertId();
      //? INSERT TO tableMain

      //? INSERT to tableSeoMeta
        $this->db->createQueryBuilder()
          ->insert($this->tableSeoMeta)
          ->values([
            'id_parent'        => ':id_parent',
            'type'             => ':type',
            'meta_title'       => ':meta_title',
            'meta_description' => ':meta_description',
            'meta_robots'      => ':meta_robots',
            'created_at'       => ':created_at',
            'updated_at'       => ':updated_at'
          ])
          ->setParameters([
            'id_parent'        => $lastTableMainId,
            'type'             => 'article_category',
            'meta_title'       => $input['meta_title'],
            'meta_description' => $input['meta_description'],
            'meta_robots'      => $input['meta_robots'],
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s')
          ])
          ->executeStatement();
        $lastTableSeoMetaId = $this->db->lastInsertId();
      //? INSERT to tableSeoMeta

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, $lastTableMainId, 'INSERT');
        $this->helper->addLog($this->db, $user, $this->tableSeoMeta, $lastTableSeoMetaId, 'INSERT');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
      //? DELETE CACHE

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
    $this->checkSlug($input);

    $this->db->beginTransaction();
    try {
      //? UPDATE ON tableMain
        $this->db->createQueryBuilder()
          ->update($this->tableMain)
          ->set('status', ':status')
          ->set('title', ':title')
          ->set('description', ':description')
          ->set('slug', ':slug')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameters([
            'id'          => (int) $input['id'],
            'title'       => $input['title'],
            'slug'        => $input['slug'],
            'description' => $input['description'],
            'status'      => $input['status'],
            'updated_at'  => date('Y-m-d H:i:s')
          ])
          ->executeStatement();
      //? UPDATE ON tableMain

      //? UPDATE ON tableSeoMeta
        $this->db->createQueryBuilder()
          ->update($this->tableSeoMeta)
          ->set('meta_title', ':meta_title')
          ->set('meta_description', ':meta_description')
          ->set('meta_robots', ':meta_robots')
          ->set('updated_at', ':updated_at')
          ->where('id_parent = :id_parent')
          ->andWhere('type = :type')
          ->setParameters([
            'id_parent'        => (int) $input['id'],
            'type'             => 'article_category',
            'meta_title'       => $input['meta_title'],
            'meta_description' => $input['meta_description'],
            'meta_robots'      => $input['meta_robots'],
            'updated_at'       => date('Y-m-d H:i:s'),
          ])
          ->executeStatement();
      //? UPDATE ON tableSeoMeta

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, (int) $input['id'], 'UPDATE');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:detail"));
      //? DELETE CACHE

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
    $countArticle = $this->checkArticleTotal($input);
    if ($countArticle) {
      throw new Exception('This category has articles, you can not delete it.');
    }

    $this->db->beginTransaction();
    try {
      //? DELETE tableMain
        $this->db->createQueryBuilder()
          ->delete($this->tableMain)
          ->where('id = :id')
          ->setParameter('id', $check['id'])
          ->executeStatement();
      //? DELETE tableMain

      //? DELETE tableSeoMeta
      $this->db->createQueryBuilder()
        ->delete($this->tableSeoMeta)
        ->where('id_parent = :id_parent')
        ->andWhere('type = :type')
        ->setParameter('id_parent', $check['id'])
        ->setParameter('type', 'article_category')
        ->executeStatement();
      //? DELETE tableSeoMeta

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, (int) $check['id'], 'DELETE');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:detail"));
      //? DELETE CACHE

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