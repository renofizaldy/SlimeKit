<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use App\Helpers\General;
use App\Lib\Cloudinary;
use App\Lib\Valkey;

use App\Models\ArticleCategory;
use App\Models\Article;
use App\Models\SeoMeta;

class AdminArticleCategoryService
{
  private string $tableMain = 'tb_article_category';
  private string $cacheKey = 'article_category';

  public function __construct(
    private General $helper,
    private Cloudinary $cloudinary,
    private Valkey $valkey
  ) {}

  private function checkExist(array $input)
  {
    $check = ArticleCategory::find($input['id']);
    if (!$check) {
      throw new Exception('Not Found', 404);
    }
    return $check;
  }

  private function checkArticleTotal(array $input)
  {
    return Article::where('id_category', (int) $input['id'])->exists();
  }

  private function invalidateCategoryAndArticleCache(): void
  {
    $this->valkey->deleteByPrefix("{$this->cacheKey}:list:");
    $this->valkey->deleteByPrefix("{$this->cacheKey}:detail:");

    $this->valkey->deleteByPrefix("article:list:");
    $this->valkey->deleteByPrefix("article:detail:");
    $this->valkey->deleteByPrefix("article:similar:");
    $this->valkey->deleteByPrefix("article:admin");
  }

  public function checkSlug(array $input)
  {
    $query = ArticleCategory::where('slug', $input['slug']);
    if (!empty($input['id'])) {
      $check = $query->first();
      if ($check && $check->id !== (int) $input['id']) {
        throw new Exception('Slug already exists', 409);
      }
      return true;
    }
    if ($query->exists()) {
      throw new Exception('Slug already exists', 409);
    }
    return true;
  }

  public function list(array $input)
  {
    $page = max(1, (int) ($input['page'] ?? 1));
    $pageSize = max(1, (int) ($input['pageSize'] ?? 10));

    $query = ArticleCategory::with('seoMeta')
      ->withCount('articles as total_article');

    if ($input['status'] !== 'all') {
      $query->where('status', $input['status']);
    }
    $query->orderBy('id', 'desc');

    return $this->helper->paginateQuery(
      $query,
      $page,
      $pageSize,
      fn ($row) => [
        'id'               => $row->id,
        'title'            => $row->title,
        'status'           => $row->status,
        'slug'             => $row->slug,
        'description'      => $row->description,
        'meta_title'       => $row->seoMeta->meta_title ?? null,
        'meta_description' => $row->seoMeta->meta_description ?? null,
        'meta_robots'      => $row->seoMeta->meta_robots ?? null,
        'total'            => $row->total_article ?? 0, // Hasil withCount
        'created_at'       => date('Y-m-d H:i:s', strtotime($row->created_at))
      ]
    );
  }

  public function detail(array $input)
  {
    $data = [];
    $event = $this->checkExist($input);
    $event->load('seoMeta');

    if ($event) {
      $data = $event->toArray();
    }
    return $data;
  }

  public function add(array $input, array $user)
  {
    $this->checkSlug($input);

    DB::beginTransaction();
    try {
      //? INSERT TO tableMain
        $category = ArticleCategory::create([
          'status'      => $input['status'],
          'title'       => $input['title'],
          'description' => $input['description'],
          'slug'        => $input['slug'],
        ]);
        $lastTableMainId = $category->id;
      //? INSERT TO tableMain

      //? INSERT to tableSeoMeta
        SeoMeta::create([
          'id_parent'        => $lastTableMainId,
          'type'             => 'article_category',
          'meta_title'       => $input['meta_title'],
          'meta_description' => $input['meta_description'],
          'meta_robots'      => $input['meta_robots'],
        ]);
      //? INSERT to tableSeoMeta

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $lastTableMainId, 'INSERT', $this->helper->logRecordChanges($category));
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $this->invalidateCategoryAndArticleCache();
      //? DELETE CACHE
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    $this->checkSlug($input);
    $check = $this->checkExist($input);

    DB::beginTransaction();
    try {
      //? UPDATE ON tableMain
        $updateData = [
          'title'       => $input['title'],
          'slug'        => $input['slug'],
          'description' => $input['description'],
          'status'      => $input['status'],
          'updated_at'  => date('Y-m-d H:i:s')
        ];
        ArticleCategory::where('id', (int) $input['id'])->update($updateData);
      //? UPDATE ON tableMain

      //? UPDATE ON tableSeoMeta
        $updateSeoData = [
          'meta_title'       => $input['meta_title'],
          'meta_description' => $input['meta_description'],
          'meta_robots'      => $input['meta_robots'],
          'updated_at'       => date('Y-m-d H:i:s'),
        ];
        SeoMeta::where('id_parent', (int) $input['id'])->where('type', 'article_category')
          ->update($updateSeoData);
      //? UPDATE ON tableSeoMeta

      //? LOG Record
        $changes = $this->helper->logUpdateChanges($check, $updateData);
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE', $changes);
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $this->invalidateCategoryAndArticleCache();
      //? DELETE CACHE
    }
    catch (Exception $e) {
      DB::rollBack();
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

    DB::beginTransaction();
    try {
      //? DELETE tableMain
        ArticleCategory::where('id', $check->id)->delete();
      //? DELETE tableMain

      //? DELETE tableSeoMeta
        SeoMeta::where('id_parent', $check->id)
          ->where('type', 'article_category')
          ->delete();
      //? DELETE tableSeoMeta

      //? LOG Record
        $changes = $this->helper->logRecordChanges($check);
        $this->helper->addLog($user, $this->tableMain, (int) $check['id'], 'DELETE', $changes);
      //? LOG Record

      DB::commit();

      //? DELETE CACHE
        $this->invalidateCategoryAndArticleCache();
      //? DELETE CACHE
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
