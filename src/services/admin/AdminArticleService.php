<?php

namespace App\Services\Admin;

use Exception;
use App\Lib\Database;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;
use App\Lib\Cronhooks;
use App\Models\Article;

class AdminArticleService
{
  private $db;
  private $helper;
  private $cloudinary;
  private $valkey;
  private $cronhooks;
  private $tableMain = 'tb_article';
  private $tableCategory = 'tb_article_category';
  private $tableSeoMeta = 'tb_seo_meta';
  private $tablePicture = 'tb_picture';
  private $tableCronhooks = 'tb_cronhooks';
  private $cacheKey = 'article';
  private $cacheExpired = (60 * 30); // 30 minutes

  public function __construct()
  {
    $this->db = (new Database())->getConnection();
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
    $this->valkey = new Valkey;
    $this->cronhooks = new Cronhooks;
  }

  private function checkExist(array $input)
  {
    $check = $this->db->createQueryBuilder()
      ->select('*')
      ->from($this->tableMain)
      ->where($this->tableMain.'.id = :id')
      ->setParameter('id', (int) $input['id'])
      ->fetchAssociative();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
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

    //* GENERATE CACHE KEY
    $cacheKey   = sprintf(
      "{$this->cacheKey}:admin:list:status=%s:featured=%s:category=%s",
      $input['status'],
      $input['featured'],
      $input['category']
    );
    $cachedData = $this->valkey->get($cacheKey);
    if ($cachedData) {
      $data = json_decode($cachedData, true);
      return $data;
    }

    $queryBuilder = $this->db->createQueryBuilder()
      ->select(
        "{$this->tableMain}.*",
        "{$this->tableCategory}.title AS category_title",
        "{$this->tableSeoMeta}.seo_keyphrase AS seo_keyphrase",
        "{$this->tableSeoMeta}.seo_analysis AS seo_analysis",
        "{$this->tableSeoMeta}.seo_readability AS seo_readability"
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tableCategory,
        $this->tableCategory,
        "{$this->tableMain}.id_category = {$this->tableCategory}.id"
      )
      ->leftJoin(
        $this->tableMain,
        $this->tableSeoMeta,
        $this->tableSeoMeta,
        "{$this->tableMain}.id = {$this->tableSeoMeta}.id_parent AND {$this->tableSeoMeta}.type = 'article'"
      );

    //? IF FILTER: STATUS
    if ($input['status'] !== 'all') {
      $queryBuilder->where($this->tableMain.'.status = :status')
        ->setParameter('status', $input['status']);
    }
    //? IF FILTER: FEATURED
    if ($input['featured'] !== 'all') {
      $queryBuilder
        ->andWhere(":featured = ANY(".$this->tableMain.".featured)")
        ->setParameter('featured', $input['featured']);
    }
    //? IF FILTER: CATEGORY
    if ($input['category'] !== 'all' && !empty($input['category'])) {
      $queryBuilder->andWhere($this->tableMain.'.id_category = :category')
        ->setParameter('category', (int) $input['category']);
    }
    $query = $queryBuilder->executeQuery()->fetchAllAssociative();

    if (!empty($query)) {
      foreach ($query as $row) {
        $data[] = [
          'id'              => $row['id'],
          'title'           => $row['title'],
          'category'        => $row['category_title'],
          'featured'        => !empty($row['featured']) ? explode(',', trim($row['featured'], '{}')) : [],
          'status'          => $row['status'],
          'slug'            => $row['slug'],
          'author'          => $row['author'],
          'seo_keyphrase'   => $row['seo_keyphrase'],
          'seo_analysis'    => $row['seo_analysis'],
          'seo_readability' => $row['seo_readability'],
          'publish'         => $row['publish'] ? date('Y-m-d H:i:s', strtotime($row['publish'])) : null
        ];
      }
    }

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    return $data;
  }

  public function detail(array $input)
  {
    $data = [];
    $detail = $this->checkExist($input);

    $query = $this->db->createQueryBuilder()
      ->select(
        $this->tableMain.'.*',
        $this->tableCategory.'.title AS category_title',
        $this->tableSeoMeta.'.meta_title',
        $this->tableSeoMeta.'.meta_description',
        $this->tableSeoMeta.'.meta_robots',
        $this->tableSeoMeta.'.seo_keyphrase',
        $this->tableSeoMeta.'.seo_analysis',
        $this->tableSeoMeta.'.seo_readability',
        $this->tablePicture.'.original AS picture_original',
        $this->tablePicture.'.thumbnail AS picture_thumbnail',
        $this->tablePicture.'.caption AS picture_caption',
      )
      ->from($this->tableMain)
      ->leftJoin(
        $this->tableMain,
        $this->tableCategory,
        $this->tableCategory,
        $this->tableMain.'.id_category = '.$this->tableCategory.'.id'
      )
      ->leftJoin(
        $this->tableMain,
        $this->tableSeoMeta,
        $this->tableSeoMeta,
        $this->tableMain.'.id = '.$this->tableSeoMeta.'.id_parent'
      )
      ->leftJoin(
        $this->tableMain,
        $this->tablePicture,
        $this->tablePicture,
        $this->tableMain.'.id_picture = '.$this->tablePicture.'.id'
      )
      ->where($this->tableMain.'.id = :id')
      ->andWhere($this->tableSeoMeta.'.type = :type')
      ->setParameter('type', 'article')
      ->setParameter('id', (int) $detail['id'])
      ->executeQuery()
      ->fetchAssociative();

    if (!empty($query)) {
      $data = [
        'article' => [
          'id'          => $query['id'],
          'title'       => $query['title'],
          'slug'        => $query['slug'],
          'excerpt'     => $query['excerpt'],
          'content'     => $query['content'],
          'author'      => $query['author'],
          'publish'     => $query['publish'] ? date('Y-m-d H:i:s', strtotime($query['publish'])) : null,
          'status'      => $query['status'],
          'featured'    => !empty($query['featured']) ? explode(',', trim($query['featured'], '{}')) : [],
          'read_time'   => $query['read_time'] ?? 0,
          'created_at'  => date('Y-m-d H:i:s', strtotime($query['created_at'])),
          'updated_at'  => date('Y-m-d H:i:s', strtotime($query['updated_at'])),
        ],
        'category' => [
          'id'          => $query['id_category'],
          'title'       => $query['category_title'],
        ],
        'seo' => [
          'keyphrase'   => $query['seo_keyphrase'],
          'analysis'    => $query['seo_analysis'],
          'readability' => $query['seo_readability'],
        ],
        'meta' => [
          'title'       => $query['meta_title'],
          'description' => $query['meta_description'],
          'robots'      => $query['meta_robots'],
        ],
        'picture' => [
          'id'          => $query['id_picture'],
          'original'    => $query['picture_original'],
          'thumbnail'   => $query['picture_thumbnail'],
          'caption'     => $query['picture_caption'],
        ]
      ];
    }

    return $data;
  }

  public function add(array $input, array $user)
  {
    $this->checkSlug($input);

    $this->db->beginTransaction();
    try {
      //? PICTURE UPLOAD
        $picture_id = $this->helper->pictureUpload($this->db, $this->cloudinary, $input['picture'] ?? null, $input['picture_caption'] ?? null);
      //? PICTURE UPLOAD

      //? INSERT TO tableMain
        $this->db->createQueryBuilder()
          ->insert($this->tableMain)
          ->values([
            'title'       => ':title',
            'slug'        => ':slug',
            'excerpt'     => ':excerpt',
            'content'     => ':content',
            'id_picture'  => ':id_picture',
            'id_category' => ':id_category',
            'author'      => ':author',
            'publish'     => ':publish',
            'status'      => ':status',
            'featured'    => ':featured',
            'read_time'   => ':read_time',
            'created_at'  => ':created_at',
            'updated_at'  => ':updated_at'
          ])
          ->setParameters([
            'title'       => $input['title'],
            'slug'        => $input['slug'],
            'excerpt'     => $input['excerpt'] ?? null,
            'content'     => $input['content'] ?? null,
            'id_picture'  => $picture_id['id'] ?? null,
            'id_category' => !empty($input['category']) ? (int)$input['category'] : null,
            'author'      => $input['author'] ?? null,
            'publish'     => !empty($input['publish']) ? date('Y-m-d H:i:s', strtotime($input['publish'])) : null,
            'status'      => $input['status'],
            'featured'    => !empty($input['featured']) ? '{' . implode(',', $input['featured']) . '}' : null,
            'read_time'   => $input['read_time'] ?? 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
          ])
          ->executeStatement();
        $lastTableMainId = $this->db->lastInsertId();
      //? INSERT TO tableMain

      //? INSERT TO tableSeoMeta
        $this->db->createQueryBuilder()
          ->insert($this->tableSeoMeta)
          ->values([
            'id_parent'        => ':id_parent',
            'type'             => ':type',
            'meta_title'       => ':meta_title',
            'meta_description' => ':meta_description',
            'meta_robots'      => ':meta_robots',
            'seo_keyphrase'    => ':seo_keyphrase',
            'seo_analysis'     => ':seo_analysis',
            'seo_readability'  => ':seo_readability',
            'created_at'       => ':created_at',
            'updated_at'       => ':updated_at'
          ])
          ->setParameters([
            'id_parent'        => $lastTableMainId,
            'type'             => 'article',
            'meta_title'       => $input['meta_title'] ?? null,
            'meta_description' => $input['meta_description'] ?? null,
            'meta_robots'      => $input['meta_robots'] ?? null,
            'seo_keyphrase'    => $input['seo_keyphrase'] ?? null,
            'seo_analysis'     => $input['seo_analysis'] ?? null,
            'seo_readability'  => $input['seo_readability'] ?? null,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s')
          ])
          ->executeStatement();
        $lastTableSeoMetaId = $this->db->lastInsertId();
      //? INSERT TO tableSeoMeta

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, $lastTableMainId, 'INSERT');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:admin"));
      //? DELETE CACHE

      $this->db->commit();

      //? CHANGE CRONJOB
        if (!empty($input['publish']) && $input['publish'] > date('Y-m-d H:i:s')) {
          $this->helper->recomputeCron($this->db, $this->cronhooks);
        }
      //? CHANGE CRONJOB

      return [
        'id' => $lastTableMainId
      ];
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
    $check = $this->checkExist($input);

    $this->db->beginTransaction();
    try {
      //? PICTURE UPLOAD
        $picture_id = $this->helper->pictureUpload($this->db, $this->cloudinary, $input['picture'] ?? null);
      //? PICTURE UPLOAD

      //? UPDATE ON tableMain
        $update = $this->db->createQueryBuilder()
          ->update($this->tableMain)
          ->set('title', ':title')
          ->set('slug', ':slug')
          ->set('excerpt', ':excerpt')
          ->set('content', ':content')
          ->set('id_category', ':id_category')
          ->set('author', ':author')
          ->set('publish', ':publish')
          ->set('status', ':status')
          ->set('featured', ':featured')
          ->set('read_time', ':read_time')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameters([
            'id'          => (int) $input['id'],
            'title'       => $input['title'],
            'slug'        => $input['slug'],
            'excerpt'     => $input['excerpt'] ?? null,
            'content'     => $input['content'] ?? null,
            'id_category' => !empty($input['category']) ? $input['category'] : null,
            'author'      => $input['author'] ?? null,
            'publish'     => !empty($input['publish']) ? date('Y-m-d H:i:s', strtotime($input['publish'])) : null,
            'status'      => $input['status'],
            'featured'    => !empty($input['featured']) ? '{' . implode(',', $input['featured']) . '}' : null,
            'read_time'   => $input['read_time'] ?? 0,
            'updated_at'  => date('Y-m-d H:i:s'),
          ]);
        if (!empty($picture_id['id'])) {
          $update->set('id_picture', ':id_picture')
            ->setParameter('id_picture', $picture_id['id']);
        }
        $update->executeStatement();
      //? UPDATE ON tableMain

      //? UPDATE ON tablePicture
        if (empty($picture_id['id']) && !empty($input['picture_caption'])) {
          $this->db->createQueryBuilder()
            ->update($this->tablePicture)
            ->set('caption', ':caption')
            ->set('updated_at', ':updated_at')
            ->where('id = :id')
            ->setParameters([
              'id'         => $check['id_picture'],
              'caption'    => $input['picture_caption'],
              'updated_at' => date('Y-m-d H:i:s'),
            ])
            ->executeStatement();
        }
      //? UPDATE ON tablePicture

      //? UPDATE ON tableSeoMeta
        $this->db->createQueryBuilder()
          ->update($this->tableSeoMeta)
          ->set('meta_title', ':meta_title')
          ->set('meta_description', ':meta_description')
          ->set('meta_robots', ':meta_robots')
          ->set('seo_keyphrase', ':seo_keyphrase')
          ->set('seo_analysis', ':seo_analysis')
          ->set('seo_readability', ':seo_readability')
          ->set('updated_at', ':updated_at')
          ->where('id_parent = :id_parent')
          ->andWhere('type = :type')
          ->setParameters([
            'id_parent'        => (int) $input['id'],
            'type'             => 'article',
            'meta_title'       => $input['meta_title'] ?? null,
            'meta_description' => $input['meta_description'] ?? null,
            'meta_robots'      => $input['meta_robots'] ?? null,
            'seo_keyphrase'    => $input['seo_keyphrase'] ?? null,
            'seo_analysis'     => $input['seo_analysis'] ?? null,
            'seo_readability'  => $input['seo_readability'] ?? null,
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
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:admin"));
      //? DELETE CACHE

      $this->db->commit();

      //? CHANGE CRONJOB
        if (!empty($input['publish'])) {
          $now = date('Y-m-d H:i:s');
          //! Case 1: Skip kalau status & publish sama persis
          if (
            ($input['status'] == $check['status'])
            && ($input['publish'] == $check['publish'])
          ) {
            return true; //! SKIP
          }
          //! Case 2: Kalau status berubah → recompute (selama publish belum lewat semua)
          if (
            $input['status'] !== $check['status']
            && ($input['publish'] > $now || $check['publish'] > $now)
          )
          {
            $this->helper->recomputeCron($this->db, $this->cronhooks, $input['site']);
            return true;
          }
          //! Case 3: Kalau publish berubah → recompute (selama salah satu masih future)
          if (
            $input['publish'] !== $check['publish']
            && ($input['publish'] > $now || $check['publish'] > $now)
          )
          {
            $this->helper->recomputeCron($this->db, $this->cronhooks, $input['site']);
            return true;
          }
        }
      //? CHANGE CRONJOB
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
          ->setParameter('type', 'article')
          ->executeStatement();
      //? DELETE tableSeoMeta

      //? DELETE picture
        if (!empty($check['id_picture'])) {
          //! GET PICTURE ID
          $getPicture = $this->db->createQueryBuilder()
            ->select('id_cloud')
            ->from($this->tablePicture)
            ->where('id = :id')
            ->setParameter('id', $check['id_picture'])
            ->fetchAssociative();
          //! DROP PICTURE ON CLOUD
          if (!empty($getPicture['id_cloud'])) {
            $this->cloudinary->delete($getPicture['id_cloud']);
          }
          //! DROP PICTURE ON DATABASE
          $this->db->createQueryBuilder()
            ->delete($this->tablePicture)
            ->where('id = :id')
            ->setParameter('id', $check['id_picture'])
            ->executeStatement();
        }
      //? DELETE picture

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, (int) $check['id'], 'DELETE');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:detail"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:admin"));
      //? DELETE CACHE

      $this->db->commit();

      //? CHANGE CRONJOB
        if ($check['publish'] > date('Y-m-d H:i:s')) {
          $this->helper->recomputeCron($this->db, $this->cronhooks);
        }
      //? CHANGE CRONJOB
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function statusChange(array $input, array $user)
  {
    //! IF ACTIVE
      if ($input['status'] == 'active') {
        $checkMain = $this->checkExist($input);
        $checkMeta = $this->checkMeta($input);
        if (
          empty($checkMain['excerpt']) ||
          empty($checkMain['content']) ||
          empty($checkMain['id_picture']) ||
          empty($checkMain['site']) ||
          empty($checkMain['id_category']) ||
          empty($checkMain['author']) ||
          empty($checkMain['publish']) ||
          empty($checkMain['read_time']) ||
          empty($checkMeta['meta_title']) ||
          empty($checkMeta['meta_description']) ||
          empty($checkMeta['meta_robots'])
        )
        {
          throw new Exception('Lengkapi beberapa field dulu', 400);
        }
      }
    //! IF ACTIVE

    $this->db->beginTransaction();
    try {
      //? UPDATE ON tableMain
        $update = $this->db->createQueryBuilder()
          ->update($this->tableMain)
          ->set('status', ':status')
          ->set('updated_at', ':updated_at')
          ->where('id = :id')
          ->setParameters([
            'id'          => (int) $input['id'],
            'status'      => $input['status'],
            'updated_at'  => date('Y-m-d H:i:s'),
          ]);
        $update->executeStatement();
      //? UPDATE ON tableMain

      //? LOG Record
        $this->helper->addLog($this->db, $user, $this->tableMain, (int) $input['id'], 'UPDATE', ['Status' => $input['status']]);
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list:site=%s", $input['site']));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:detail:site=%s", $input['site']));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:admin"));
      //? DELETE CACHE

      $this->db->commit();

      //? CHANGE CRONJOB
        if (!empty($checkMain['publish'])) {
          $now = date('Y-m-d H:i:s');
          //! Case 2: Kalau status berubah → recompute (selama publish belum lewat semua)
          if (
            $input['status'] !== $checkMain['status']
            && ($checkMain['publish'] > $now)
          )
          {
            $this->helper->recomputeCron($this->db, $this->cronhooks, $input['site']);
            return true;
          }
        }
      //? CHANGE CRONJOB
    }
    catch (Exception $e) {
      if ($this->db->isTransactionActive()) {
        $this->db->rollBack();
      }
      throw $e;
    }
  }

  public function addPicture(array $input, array $user)
  {
    $upload = $this->helper->pictureUpload($this->db, $this->cloudinary, $input['picture'] ?? null);
    return $upload;
  }
}