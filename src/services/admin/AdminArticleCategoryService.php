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
  private $helper;
  private $cloudinary;
  private $valkey;
  private $tableMain = 'tb_article_category';
  private $tableSeoMeta = 'tb_seo_meta';
  private $tableArticle = 'tb_article';
  private $cacheKey = 'article_category';

  public function __construct()
  {
    $this->helper = new General;
    $this->cloudinary = new Cloudinary;
    $this->valkey = new Valkey;
  }

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

  public function checkSlug(array $input)
  {
    $query = ArticleCategory::where( 'slug', $input['slug']);
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
    $data = [];

    $query = ArticleCategory::with('seoMeta')
      ->withCount(['articles as total_article']);

    if ($input['status'] !== 'all') {
      $query->where('status', $input['status']);
    }
    $results = $query->get();

    if ($results->isNotEmpty()) {
      foreach ($results as $row) {
        $data[] = [
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
        ];
      }
    }
    return $data;
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
        $seo = SeoMeta::create([
          'id_parent'        => $lastTableMainId,
          'type'             => 'article_category',
          'meta_title'       => $input['meta_title'],
          'meta_description' => $input['meta_description'],
          'meta_robots'      => $input['meta_robots'],
        ]);
        $lastTableSeoMetaId = $seo->id;
      //? INSERT to tableSeoMeta

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, $lastTableMainId, 'INSERT');
        $this->helper->addLog($user, $this->tableSeoMeta, $lastTableSeoMetaId, 'INSERT');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
      //? DELETE CACHE

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function edit(array $input, array $user)
  {
    $this->checkSlug($input);

    DB::beginTransaction();
    try {
      //? UPDATE ON tableMain
        ArticleCategory::where('id', (int) $input['id'])->update([
          'title'       => $input['title'],
          'slug'        => $input['slug'],
          'description' => $input['description'],
          'status'      => $input['status'],
          'updated_at'  => date('Y-m-d H:i:s')
        ]);
      //? UPDATE ON tableMain

      //? UPDATE ON tableSeoMeta
        SeoMeta::where('id_parent', (int) $input['id'])->where('type', 'article_category')
          ->update([
              'meta_title'       => $input['meta_title'],
              'meta_description' => $input['meta_description'],
              'meta_robots'      => $input['meta_robots'],
              'updated_at'       => date('Y-m-d H:i:s'),
          ]);
      //? UPDATE ON tableSeoMeta

      //? LOG Record
        $this->helper->addLog($user, $this->tableMain, (int) $input['id'], 'UPDATE');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:detail"));
      //? DELETE CACHE

      DB::commit();
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
        $this->helper->addLog($user, $this->tableMain, (int) $check['id'], 'DELETE');
      //? LOG Record

      //? DELETE CACHE
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:list"));
        $this->valkey->deleteByPrefix(sprintf("{$this->cacheKey}:detail"));
      //? DELETE CACHE

      DB::commit();
    }
    catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}