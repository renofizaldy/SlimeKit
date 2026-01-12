<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;
use App\Lib\Cronhooks;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Picture;
use App\Models\SeoMeta;

class AdminArticleService
{
  private $helper;
  private $cloudinary;
  private $valkey;
  private $cronhooks;

  private $cacheKey = 'article';
  private $cacheExpired = (60 * 30); // 30 minutes

  public function __construct()
  {
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
    $this->valkey = new Valkey;
    $this->cronhooks = new Cronhooks;
  }

  private function checkExist(array $input)
  {
    $check = Article::find($input['id']);
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function checkSlug(array $input)
  {
    $query = Article::where('slug', $input['slug']);
    if (!empty($input['id'])) {
      $query->where('id', '!=', $input['id']);
    }
    if ($query->exists()) {
      throw new Exception('Slug already exists', 400);
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
      return json_decode($cachedData, true);
    }

    //* BUILD QUERY
    $query = Article::with(['category', 'seoMeta']);
    //? IF FILTER: STATUS
    if ($input['status'] !== 'all') {
      $query->where('status', $input['status']);
    }
    //? IF FILTER: FEATURED
    if ($input['featured'] !== 'all') {
      $query->whereJsonContains('featured', $input['featured']);
    }
    //? IF FILTER: CATEGORY
    if ($input['category'] !== 'all' && !empty($input['category'])) {
      $query->where('id_category', (int) $input['category']);
    }
    $results = $query->orderBy('id', 'desc')->get();
    if ($results->isNotEmpty()) {
      $data = $results->map(function ($row) {
        return [
          'id'              => $row->id,
          'title'           => $row->title,
          'category'        => $row->category ? $row->category->title : null,
          'featured'        => $row->featured ?? [],
          'status'          => $row->status,
          'slug'            => $row->slug,
          'author'          => $row->author,
          'seo_keyphrase'   => $row->seoMeta ? $row->seoMeta->seo_keyphrase : null,
          'seo_analysis'    => $row->seoMeta ? $row->seoMeta->seo_analysis : null,
          'seo_readability' => $row->seoMeta ? $row->seoMeta->seo_readability : null,
          'publish'         => $row->publish ? date('Y-m-d H:i:s', strtotime($row->publish)) : null
        ];
      })->toArray();
    }

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    return $data;
  }

  public function detail(array $input)
  {
    $article = $this->checkExist($input);
    $article->load(['category', 'seoMeta', 'picture']);

    $data = [
      'article' => [
        'id'            => $article->id,
        'title'         => $article->title,
        'slug'          => $article->slug,
        'excerpt'       => $article->excerpt,
        'content'       => $article->content,
        'author'        => $article->author,
        'publish'       => $article->publish ? date('Y-m-d H:i:s', strtotime($article->publish)) : null,
        'status'        => $article->status,
        'featured'      => $article->featured ?? [],
        'read_time'     => $article->read_time ?? 0,
        'created_at'    => $article->created_at ? $article->created_at->format('Y-m-d H:i:s') : null,
        'updated_at'    => $article->updated_at ? $article->updated_at->format('Y-m-d H:i:s') : null,
      ],
      'category' => [
        'id'            => $article->id_category,
        'title'         => $article->category->title ?? null,
      ],
      'seo' => [
        'keyphrase'     => $article->seoMeta->seo_keyphrase ?? null,
        'analysis'      => $article->seoMeta->seo_analysis ?? null,
        'readability'   => $article->seoMeta->seo_readability ?? null,
      ],
      'meta' => [
        'title'         => $article->seoMeta->meta_title ?? null,
        'description'   => $article->seoMeta->meta_description ?? null,
        'robots'        => $article->seoMeta->meta_robots ?? null,
      ],
      'picture' => [
        'id'            => $article->id_picture,
        'original'      => $article->picture->original ?? null,
        'thumbnail'     => $article->picture->thumbnail ?? null,
        'caption'       => $article->picture->caption ?? null,
      ]
    ];

    return $data;
  }

  public function add(array $input, array $user)
  {
    $this->checkSlug($input);

    DB::beginTransaction();
    try {
      //? PICTURE UPLOAD
      $picture_id = $this->helper->pictureUpload($this->cloudinary, $input['picture'] ?? null, $input['picture_caption'] ?? null);

      //? INSERT TO tableMain (Article)
      $article = Article::create([
        'title'       => $input['title'],
        'slug'        => $input['slug'],
        'excerpt'     => $input['excerpt'] ?? null,
        'content'     => $input['content'] ?? null,
        'id_picture'  => $picture_id['id'] ?? null,
        'id_category' => !empty($input['category']) ? (int)$input['category'] : null,
        'author'      => $input['author'] ?? null,
        'publish'     => !empty($input['publish']) ? date('Y-m-d H:i:s', strtotime($input['publish'])) : null,
        'status'      => $input['status'],
        'featured'    => $input['featured'] ?? [],
        'read_time'   => $input['read_time'] ?? 0,
      ]);

      //? INSERT TO tableSeoMeta
      SeoMeta::create([
        'id_parent'        => $article->id,
        'type'             => 'article',
        'meta_title'       => $input['meta_title'] ?? null,
        'meta_description' => $input['meta_description'] ?? null,
        'meta_robots'      => $input['meta_robots'] ?? null,
        'seo_keyphrase'    => $input['seo_keyphrase'] ?? null,
        'seo_analysis'     => $input['seo_analysis'] ?? null,
        'seo_readability'  => $input['seo_readability'] ?? null,
      ]);

      //? LOG Record
      $this->helper->addLog($user, 'tb_article', $article->id, 'INSERT');

      //? DELETE CACHE
      $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
      $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:admin"));

      DB::commit();

      //? CHANGE CRONJOB
      if (!empty($input['publish']) && $input['publish'] > date('Y-m-d H:i:s')) {
        $this->helper->recomputeCron($this->cronhooks);
      }

      return [
        'id' => $article->id
      ];

    } catch (Exception $e) {
      DB::rollBack();
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