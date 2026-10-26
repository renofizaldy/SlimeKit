<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;
use App\Lib\Cronhooks;
use App\Lib\NextRevalidator;

use App\Models\Article;
use App\Models\Picture;
use App\Models\SeoMeta;
use App\Models\ArticleAgent;

class AdminArticleService
{
  private string $tableMain = 'tb_article';
  private string $cacheKey = 'article';
  private int $cacheExpired = (60 * 30); // 30 minutes

  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary,
    private Valkey $valkey,
    private Cronhooks $cronhooks,
    private NextRevalidator $nextRevalidator
  ) {}

  private function shouldRevalidateArticle($status, $publish): bool
  {
    return $status === 'active'
      && !empty($publish)
      && strtotime((string) $publish) <= time();
  }

  private function revalidateArticle(?string $slug, ?string $oldSlug = null): void
  {
    if (empty($slug)) {
      return;
    }

    $this->nextRevalidator->revalidateArticle($slug, $oldSlug);
  }

  private function invalidateArticleCache(): void
  {
    $this->valkey->deleteByPrefix("{$this->cacheKey}:list:");
    $this->valkey->deleteByPrefix("{$this->cacheKey}:detail:");
    $this->valkey->deleteByPrefix("{$this->cacheKey}:similar:");
    $this->valkey->deleteByPrefix("{$this->cacheKey}:admin");
  }

  private function checkExist(array $input)
  {
    $check = Article::find($input['id']);
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  private function checkMeta(array $input)
  {
    $check = SeoMeta::where([
      'id_parent' => (int) $input['id'],
      'type'      => 'article'
    ])->first();
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  public function checkSlug(array $input)
  {
    $query = Article::where('slug', $input['slug']);
    if (!empty($input['id'])) {
      $query->where('id', '!=', (int) $input['id']);
    }
    if ($query->exists()) {
      throw new Exception('Slug already exists', 409);
    }
    return true;
  }

  public function list(array $input)
  {
    $page           = max(1, (int) ($input['page'] ?? 1));
    $pageSize       = max(1, (int) ($input['pageSize'] ?? 10));
    $allowedOrderBy = ['publish', 'id', 'title', 'status', 'author', 'created_at'];
    $orderBy        = in_array($input['orderBy'] ?? '', $allowedOrderBy, true) ? $input['orderBy'] : 'publish';
    $orderDir       = strtolower($input['orderDir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    //* GENERATE CACHE KEY
      $cacheKey   = sprintf(
        "{$this->cacheKey}:admin:list:status=%s:featured=%s:category=%s:search=%s:page=%d:pageSize=%d:orderBy=%s:orderDir=%s",
        $input['status'],
        $input['featured'],
        $input['category'],
        $input['search'] ?? '',
        $page,
        $pageSize,
        $orderBy,
        $orderDir
      );
      $cachedData = $this->valkey->get($cacheKey);
      if ($cachedData) {
        return json_decode($cachedData, true);
      }
    //* GENERATE CACHE KEY

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
      //? IF FILTER: SEARCH
      if (!empty($input['search'])) {
        $searchTerm = '%' . $input['search'] . '%';
        $query->where(function ($q) use ($searchTerm) {
          $q->where('title', 'LIKE', $searchTerm)
            ->orWhere('author', 'LIKE', $searchTerm)
            ->orWhere('slug', 'LIKE', $searchTerm);
        });
      }
      $query->orderBy($orderBy, $orderDir);
      if ($orderBy !== 'id') {
        $query->orderBy('id', 'desc');
      }

      $data = $this->helper->paginateQuery(
        $query,
        $page,
        $pageSize,
        fn ($row) => [
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
        ]
      );
    //* BUILD QUERY

    //* SAVE TO CACHE
    $this->valkey->set($cacheKey, json_encode($data), $this->cacheExpired-1); //! Valkey Expired

    return $data;
  }

  public function detail(array $input)
  {
    $data = [];
    $detail = $this->checkExist($input);
    $detail->load(['category', 'seoMeta', 'picture']);

    $data = [
      'article' => [
        'id'          => $detail->id,
        'title'       => $detail->title,
        'slug'        => $detail->slug,
        'excerpt'     => $detail->excerpt,
        'content'     => $detail->content,
        'author'      => $detail->author,
        'publish'     => $detail->publish ? date('Y-m-d H:i:s', strtotime($detail->publish)) : null,
        'status'      => $detail->status,
        'featured'    => $detail->featured ?? [], // Auto-cast dari Model
        'read_time'   => $detail->read_time ?? 0,
        'created_at'  => $detail->created_at ? $detail->created_at->format('Y-m-d H:i:s') : null,
        'updated_at'  => $detail->updated_at ? $detail->updated_at->format('Y-m-d H:i:s') : null,
      ],
      'category' => [
        'id'          => $detail->id_category,
        'title'       => $detail->category->title ?? null,
      ],
      'seo' => [
        'keyphrase'   => $detail->seoMeta->seo_keyphrase ?? null,
        'analysis'    => $detail->seoMeta->seo_analysis ?? null,
        'readability' => $detail->seoMeta->seo_readability ?? null,
      ],
      'meta' => [
        'title'       => $detail->seoMeta->meta_title ?? null,
        'description' => $detail->seoMeta->meta_description ?? null,
        'robots'      => $detail->seoMeta->meta_robots ?? null,
      ],
      'picture' => [
        'id'          => $detail->id_picture,
        'original'    => $detail->picture->original ?? null,
        'large'       => $this->helper->formatCloudinaryUrl($detail->picture->original ?? $detail->picture->thumbnail ?? null, 'c_fill,w_1200,h_675,g_auto,q_auto,f_auto'),
        'thumbnail'   => $detail->picture->thumbnail ?? null,
        'caption'     => $detail->picture->caption ?? null,
      ]
    ];

    return $data;
  }

  public function add(array $input, array $user)
  {
    $this->checkSlug($input);

    //? PICTURE UPLOAD OUTSIDE TRANSACTION
    $uploadedPicture = !empty($input['picture']) ? $this->helper->uploadImage($this->cloudinary, $input['picture']) : null;

    DB::beginTransaction();
    try {
      //? PICTURE SAVE
        $picture = $uploadedPicture ? $this->helper->savePicture($uploadedPicture, $input['picture_caption'] ?? null) : null;
        $picture_id = $picture ? ['id' => $picture->id, 'url' => $uploadedPicture['original']] : null;
      //? PICTURE SAVE

      //? INSERT TO tableMain
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
          'featured'    => !empty($input['featured']) ? $input['featured'] : null,
          'read_time'   => $input['read_time'] ?? 0,
        ]);
        $lastTableMainId = $article->id;
      //? INSERT TO tableMain

      //? INSERT TO tableSeoMeta
        SeoMeta::create([
          'id_parent'        => $lastTableMainId,
          'type'             => 'article',
          'meta_title'       => $input['meta_title'] ?? null,
          'meta_description' => $input['meta_description'] ?? null,
          'meta_robots'      => $input['meta_robots'] ?? null,
          'seo_keyphrase'    => $input['seo_keyphrase'] ?? null,
          'seo_analysis'     => $input['seo_analysis'] ?? null,
          'seo_readability'  => $input['seo_readability'] ?? null,
        ]);
      //? INSERT TO tableSeoMeta

      //? INSERT TO tableArticleAgent
        if (!empty($input['article_agent'])) {
          ArticleAgent::where('id', $input['article_agent'])->update([
            'id_parent' => $lastTableMainId
          ]);
        }
      //? INSERT TO tableArticleAgent

      //? LOG Record
        $changes = $this->helper->logRecordChanges($article);
        $this->helper->addLog($user, $this->tableMain, $lastTableMainId, 'INSERT', $changes);
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $this->invalidateArticleCache();
      //? DELETE CACHE

      //? REVALIDATE ARTICLE
        if ($this->shouldRevalidateArticle($input['status'], $input['publish'] ?? null)) {
          $this->revalidateArticle($input['slug']);
        }
      //? REVALIDATE ARTICLE

      //? CHANGE CRONJOB
        if (!empty($input['publish']) && $input['publish'] > date('Y-m-d H:i:s')) {
          $this->helper->reCron($this->cronhooks);
        }
      //? CHANGE CRONJOB

      return [
        'id' => $lastTableMainId
      ];
    }
    catch (Exception $e) {
      DB::rollBack();
      if ($uploadedPicture && !empty($uploadedPicture['public_id'])) {
        try {
          $this->cloudinary->delete($uploadedPicture['public_id']);
        } catch (\Throwable) {}
      }
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    $this->checkSlug($input);
    $check = $this->checkExist($input);

    //? PICTURE UPLOAD OUTSIDE TRANSACTION
    $uploadedPicture = !empty($input['picture']) ? $this->helper->uploadImage($this->cloudinary, $input['picture']) : null;

    DB::beginTransaction();
    try {
      //? PICTURE SAVE
        $picture = $uploadedPicture ? $this->helper->savePicture($uploadedPicture, $input['picture_caption'] ?? null) : null;
        $picture_id = $picture ? ['id' => $picture->id, 'url' => $uploadedPicture['original']] : null;
      //? PICTURE SAVE

      //? UPDATE ON tableMain
        $updateData = [
          'title'       => $input['title'],
          'slug'        => $input['slug'],
          'excerpt'     => $input['excerpt'] ?? null,
          'content'     => $input['content'] ?? null,
          'id_category' => !empty($input['category']) ? (int) $input['category'] : null,
          'author'      => $input['author'] ?? null,
          'publish'     => !empty($input['publish']) ? date('Y-m-d H:i:s', strtotime($input['publish'])) : null,
          'status'      => $input['status'],
          'featured'    => !empty($input['featured']) ? $input['featured'] : null,
          'read_time'   => $input['read_time'] ?? 0,
          'updated_at'  => date('Y-m-d H:i:s'),
        ];
        if (!empty($picture_id['id'])) {
          $updateData['id_picture'] = $picture_id['id'];
        }
        Article::where('id', (int) $input['id'])->update($updateData);
      //? UPDATE ON tableMain

      //? UPDATE ON tablePicture
        if (empty($picture_id['id']) && !empty($input['picture_caption']) && !empty($check->id_picture)) {
          Picture::where('id', $check->id_picture)->update([
            'caption' => $input['picture_caption'],
          ]);
        }
      //? UPDATE ON tablePicture

      //? UPDATE ON tableSeoMeta
        $updateSeoData = [
          'meta_title'       => $input['meta_title'] ?? null,
          'meta_description' => $input['meta_description'] ?? null,
          'meta_robots'      => $input['meta_robots'] ?? null,
          'seo_keyphrase'    => $input['seo_keyphrase'] ?? null,
          'seo_analysis'     => $input['seo_analysis'] ?? null,
          'seo_readability'  => $input['seo_readability'] ?? null,
          'updated_at'       => date('Y-m-d H:i:s'),
        ];
        SeoMeta::where('id_parent', (int) $input['id'])
          ->where('type', 'article')
          ->update($updateSeoData);
      //? UPDATE ON tableSeoMeta

      //? UPDATE ON tableArticleAgent
        if (!empty($input['article_agent'])) {
          ArticleAgent::where('id', $input['article_agent'])->update([
            'id_parent' => $input['id']
          ]);
        }
      //? UPDATE ON tableArticleAgent

      //? LOG Record
        $changes = $this->helper->logUpdateChanges($check, $updateData);
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE', $changes);
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $this->invalidateArticleCache();
      //? DELETE CACHE

      //? REVALIDATE ARTICLE
        if (
          $this->shouldRevalidateArticle($input['status'], $input['publish'] ?? null)
          || $this->shouldRevalidateArticle($check->status, $check->publish)
        ) {
          $oldSlug = ($check->slug !== $input['slug']) ? $check->slug : null;
          $this->revalidateArticle($input['slug'], $oldSlug);
        }
      //? REVALIDATE ARTICLE

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
            $this->helper->reCron($this->cronhooks);
            return true;
          }
          //! Case 3: Kalau publish berubah → recompute (selama salah satu masih future)
          if (
            $input['publish'] !== $check['publish']
            && ($input['publish'] > $now || $check['publish'] > $now)
          )
          {
            $this->helper->reCron($this->cronhooks);
            return true;
          }
        }
      //? CHANGE CRONJOB

      return true;
    }
    catch (Exception $e) {
      DB::rollBack();
      if ($uploadedPicture && !empty($uploadedPicture['public_id'])) {
        try {
          $this->cloudinary->delete($uploadedPicture['public_id']);
        } catch (\Throwable) {}
      }
      throw $e;
    }
  }

  public function drop(array $input, array $user)
  {
    $check = $this->checkExist($input);
    $pictureCloudId = null;

    DB::beginTransaction();
    try {
      //? DELETE tableMain
        Article::where('id', $check->id)->delete();
      //? DELETE tableMain

      //? DELETE tableSeoMeta
        SeoMeta::where('id_parent', $check->id)
          ->where('type', 'article')
          ->delete();
      //? DELETE tableSeoMeta

      //? DELETE picture
        if (!empty($check->id_picture)) {
          $picture = Picture::find($check->id_picture);
          if ($picture) {
            $pictureCloudId = $picture->id_cloud ?? null;
            //! DROP PICTURE ON DATABASE
            $picture->delete();
          }
        }
      //? DELETE picture

      //? LOG Record
        $changes = $this->helper->logRecordChanges($check);
        $this->helper->addLog($user, $this->tableMain, (int) $check['id'], 'DELETE', $changes);
      //? LOG Record

      DB::commit();

      //! DROP PICTURE ON CLOUD (AFTER COMMIT)
      if (!empty($pictureCloudId)) {
        try {
          $this->cloudinary->delete($pictureCloudId);
        } catch (\Throwable $e) {
          error_log('Failed to delete Cloudinary picture after drop: ' . $e->getMessage());
        }
      }

      //? DELETE CACHE
        $this->invalidateArticleCache();
      //? DELETE CACHE

      //? REVALIDATE ARTICLE
        if ($this->shouldRevalidateArticle($check['status'], $check['publish'])) {
          $this->revalidateArticle($check['slug']);
        }
      //? REVALIDATE ARTICLE

      //? CHANGE CRONJOB
        if ($check['publish'] > date('Y-m-d H:i:s')) {
          $this->helper->reCron($this->cronhooks);
        }
      //? CHANGE CRONJOB
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function statusChange(array $input, array $user)
  {
    $checkMain = $this->checkExist($input);

    //! IF ACTIVE
      if ($input['status'] == 'active') {
        $checkMeta = $this->checkMeta($input);
        if (
          empty($checkMain->excerpt) ||
          empty($checkMain->content) ||
          empty($checkMain->id_picture) ||
          empty($checkMain->id_category) ||
          empty($checkMain->author) ||
          empty($checkMain->publish) ||
          empty($checkMain->read_time) ||
          empty($checkMeta->meta_title) ||
          empty($checkMeta->meta_description) ||
          empty($checkMeta->meta_robots)
        )
        {
          throw new Exception('Lengkapi beberapa field dulu', 400);
        }
      }
    //! IF ACTIVE

    DB::beginTransaction();
    try {
      //? UPDATE ON tableMain
        Article::where('id', (int) $input['id'])->update([
          'status' => $input['status']
        ]);
      //? UPDATE ON tableMain

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE', $this->helper->logUpdateChanges($checkMain, [
          'status' => $input['status']
        ]));
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $this->invalidateArticleCache();
      //? DELETE CACHE

      //? REVALIDATE ARTICLE
        if (
          $this->shouldRevalidateArticle($input['status'], $checkMain['publish'])
          || $this->shouldRevalidateArticle($checkMain['status'], $checkMain['publish'])
        ) {
          $this->revalidateArticle($checkMain['slug']);
        }
      //? REVALIDATE ARTICLE

      //? CHANGE CRONJOB
        if (!empty($checkMain['publish'])) {
          $now = date('Y-m-d H:i:s');
          //! Case 2: Kalau status berubah → recompute (selama publish belum lewat semua)
          if (
            $input['status'] !== $checkMain['status']
            && ($checkMain['publish'] > $now)
          )
          {
            $this->helper->reCron($this->cronhooks);
            return true;
          }
        }
      //? CHANGE CRONJOB

      return true;
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function publishChange(array $input, array $user)
  {
    $check = $this->checkExist($input);
    DB::beginTransaction();
    try {
      //? UPDATE ON tableMain
        $updateData = [
          'publish' => !empty($input['publish']) ? date('Y-m-d H:i:s', strtotime($input['publish'])) : null
        ];
        Article::where('id', (int) $input['id'])->update($updateData);
      //? UPDATE ON tableMain

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE', $this->helper->logUpdateChanges($check, $updateData));
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $this->invalidateArticleCache();
      //? DELETE CACHE

      //? REVALIDATE ARTICLE
        if (
          $this->shouldRevalidateArticle($check['status'], $input['publish'] ?? null)
          || $this->shouldRevalidateArticle($check['status'], $check['publish'])
        ) {
          $this->revalidateArticle($check['slug']);
        }
      //? REVALIDATE ARTICLE

      //? CHANGE CRONJOB
        if (!empty($input['publish'])) {
          $now = date('Y-m-d H:i:s');
          //! Case 2: Kalau publish berubah → recompute (selama salah satu masih future)
          if (($input['publish'] > $now) || ($check['publish'] > $now))
          {
            $this->helper->reCron($this->cronhooks);
            return true;
          }
        }
      //? CHANGE CRONJOB

      return true;
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function addPicture(array $input, array $user)
  {
    $upload = $this->helper->pictureUpload($this->cloudinary, $input['picture'] ?? null);
    return $upload;
  }
}
